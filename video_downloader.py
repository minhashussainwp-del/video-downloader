from flask import Flask, request, jsonify
import yt_dlp

app = Flask(__name__)

@app.route('/')
def home():
    return "✨ Video Downloader API is Running ✨"

@app.route('/api')
def download_video():
    url = request.args.get('url')
    if not url:
        return jsonify({"error": "Missing URL parameter"}), 400

    try:
        ydl_opts = {
            'format': 'best',
            'noplaylist': True,
            'quiet': True,
            'skip_download': True,  # Only return info, no download
        }
        with yt_dlp.YoutubeDL(ydl_opts) as ydl:
            info = ydl.extract_info(url, download=False)
            formats = []
            for f in info.get('formats', []):
                if f.get('url'):
                    formats.append({
                        "format": f.get('format_note', f.get('ext')),
                        "url": f.get('url')
                    })
            return jsonify({
                "title": info.get('title'),
                "thumbnail": info.get('thumbnail'),
                "formats": formats
            })
    except Exception as e:
        return jsonify({"error": str(e)}), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=8080)
