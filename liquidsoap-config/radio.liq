set("log.file.path", "/tmp/liquidsoap.log")
set("server.telnet", true)

radio = single("/stations/music/example_music.mp3")

# --- 1 ---
radio_1 = fallback([playlist(mode="random", "/stations/1/music/"), radio])
output.icecast(%mp3(bitrate=64), host="icecast", port=8000, password="SignalFRAME", mount="/onelow", name="1 (Low)", description="1 (Low)", radio_1)
output.icecast(%mp3(bitrate=128), host="icecast", port=8000, password="SignalFRAME", mount="/onemedium", name="1 (Medium)", description="1 (Medium)", radio_1)
output.icecast(%mp3(bitrate=128), host="icecast", port=8000, password="SignalFRAME", mount="/one", name="1", description="1", radio_1)
output.icecast(%mp3(bitrate=192), host="icecast", port=8000, password="SignalFRAME", mount="/onehigh", name="1 (High)", description="1 (High)", radio_1)
