<?php

namespace App\Services\FaceRecognition;

use InvalidArgumentException;
use RuntimeException;

class FaceRecognitionManager
{
    public function driver(): FaceRecognitionDriver
    {
        return match (config('fotx.face_recognition_driver', 'mock')) {
            'mock' => app()->isProduction()
                ? throw new RuntimeException('Reconhecimento facial mock não pode ser usado em produção.')
                : app(MockFaceRecognitionDriver::class),
            'rekognition' => app(RekognitionFaceRecognitionDriver::class),
            default => throw new InvalidArgumentException('Driver de reconhecimento facial invalido.'),
        };
    }
}
