<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Models\MediaObject;
use App\Data\MediaObjectOutputData;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MediaObjectProcessor implements ProcessorInterface
{
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $request = request();
        
        Log::info('MediaObject upload request', [
            'has_file' => $request->hasFile('file'),
            'content_type' => $request->header('Content-Type'),
            'all_keys' => array_keys($request->all())
        ]);

        if (!$request->hasFile('file')) {
            throw new \InvalidArgumentException('Aucun fichier fourni');
        }

        $file = $request->file('file');
        
        // 🔧 FIX: Validation correcte Laravel
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:jpeg,png,jpg,gif,webp,heic,avif|max:5120' // 5MB max
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException('Validation échouée: ' . $validator->errors()->first());
        }

        try {
            // Upload du fichier
            $path = $file->store('media', 'public');
            
            Log::info('Fichier uploadé', [
                'original_name' => $file->getClientOriginalName(),
                'stored_path' => $path,
                'size' => $file->getSize()
            ]);
            
            // Créer l'objet MediaObject
            $mediaObject = MediaObject::create([
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ]);

            Log::info('MediaObject créé', ['id' => $mediaObject->id]);

            return MediaObjectOutputData::fromMediaObject($mediaObject);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'upload', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \InvalidArgumentException('Erreur lors de l\'upload: ' . $e->getMessage());
        }
    }
}