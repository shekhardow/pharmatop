<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ConsumeKafkaPlayback extends Command
{
    protected $signature = 'kafka:consume-playback';
    protected $description = 'Consume video playback data from Kafka and update the database';

    public function handle()
    {
        // Kafka::consume(['video-playback-topic'])
        //     ->withHandler(function (Message $message) {
        //         $data = $message->getBody();
        //         $userId = $data['user_id'];
        //         $videoId = $data['video_id'];
        //         $courseId = $data['course_id'];
        //         $lastPlaybackTime = $data['last_playback_time'];

        //         // Fetch the last saved playback data for the user and video
        //         $playback = DB::table('user_course_videos_status')
        //             ->where('user_id', $userId)
        //             ->where('course_id', $courseId)
        //             ->where('video_id', $videoId)
        //             ->first();

        //         if (!$playback || Carbon::parse($playback->updated_at)->diffInSeconds(now()) > 1) {
        //             // Update the database if the data is paused for more than 1 second
        //             DB::table('user_course_videos_status')->updateOrInsert(
        //                 ['user_id' => $userId, 'course_id' => $courseId, 'video_id' => $videoId],
        //                 ['last_playback_time' => $lastPlaybackTime, 'updated_at' => now()]
        //             );
        //         }
        //     })
        //     ->build()
        //     ->consume();
    }
}
