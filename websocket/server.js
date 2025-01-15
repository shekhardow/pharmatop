const express = require("express");
const http = require("http");
const socketIo = require("socket.io");
const axios = require("axios");
require("dotenv").config();

const app = express();
const server = http.createServer(app);

const io = socketIo(server);

const apiUrl = process.env.API_URL || "http://localhost:3000";
const port = process.env.PORT || 6001;

let playbackData = {};

io.on("connection", (socket) => {
    console.log("A user connected");
    console.log("API URL:", process.env.API_URL);

    socket.on("playback-update", async (data) => {
        const { user_id, video_id, course_id, playback_time } = data;

        const key = `${user_id}-${video_id}-${course_id}`;

        if (!playbackData[key]) {
            playbackData[key] = {
                last_playback_time: playback_time,
                last_update: Date.now(),
                dbUpdated: false,
            };
            console.log(
                "First playback update for this user-video-course combination."
            );
        } else {
            const timeDifference =
                (Date.now() - playbackData[key].last_update) / 1000;

            if (timeDifference > 3 && !playbackData[key].dbUpdated) {
                console.log(
                    "Updating DB because playback time exceeds 1 second difference"
                );

                try {
                    const response = await axios.post(
                        `${apiUrl}/api/update-playback`,
                        {
                            user_id,
                            video_id,
                            course_id,
                            playback_time,
                        }
                    );
                    console.log("Backend response:", response.data);
                } catch (error) {
                    console.error("Error updating playback data in DB:", error);
                }

                playbackData[key].last_playback_time = playback_time;
                playbackData[key].last_update = Date.now();
                playbackData[key].dbUpdated = true;
            } else {
                console.log(
                    "Playback time difference is less than 1 second. No DB update."
                );
                playbackData[key].dbUpdated = false;
            }
        }
        socket.emit("playback-ack", { status: "received", data: data });
    });

    socket.on("disconnect", () => {
        console.log("A user disconnected");
    });
});

server.listen(port, () => {
    console.log(`Socket server is running on ${port}`);
});
