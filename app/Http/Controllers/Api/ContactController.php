<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    /**
     * Envoyer un message de contact par email
     */
    public function sendContactMessage(Request $request)
    {
        // Validation
        $validated = $request->validate([
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|min:10',
            'newsletter' => 'boolean',
            'privacy' => 'accepted', // Doit être true
        ], [
            'firstName.required' => 'Le prénom est requis',
            'lastName.required' => 'Le nom est requis',
            'email.required' => "L'email est requis",
            'email.email' => 'Format d\'email invalide',
            'subject.required' => 'Le sujet est requis',
            'message.required' => 'Le message est requis',
            'message.min' => 'Le message doit contenir au moins 10 caractères',
            'privacy.accepted' => 'Vous devez accepter la politique de confidentialité',
        ]);

        try {
            $fullName = "{$validated['firstName']} {$validated['lastName']}";
            
            // Email à l'équipe
            Mail::send('email.contact', [
                'fullName' => $fullName,
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? 'Non fourni',
                'subject' => $validated['subject'],
                'messageContent' => $validated['message'],
                'newsletter' => $validated['newsletter'] ?? false,
            ], function ($mail) use ($validated, $fullName) {
                $mail->to(env('CONTACT_EMAIL', 'contact@campcameleonx.com'))
                     ->replyTo($validated['email'], $fullName)
                     ->subject("Nouveau message de contact: {$validated['subject']}");
            });

            // Email de confirmation à l'utilisateur
            Mail::send('email.contact-confirmation', [
                'fullName' => $fullName,
            ], function ($mail) use ($validated, $fullName) {
                $mail->to($validated['email'], $fullName)
                     ->subject('Votre message a bien été reçu - CampCameleonX');
            });

            Log::info('📧 Message de contact envoyé', [
                'from' => $validated['email'],
                'subject' => $validated['subject'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.',
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ Erreur envoi message de contact', [
                'error' => $e->getMessage(),
                'email' => $validated['email'],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'envoi du message. Veuillez réessayer.',
            ], 500);
        }
    }
}