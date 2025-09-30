<?php

namespace App\State\Reservations;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Models\Reservation;
use Carbon\Carbon;

class CheckInProcessor implements ProcessorInterface
{
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var Reservation $reservation */
        $reservation = $data; // API Platform injecte l'item

        if (!$reservation->canCheckIn()) {
            abort(422, 'Check-in non autorisé pour cet état ou déjà effectué.');
        }

        $at = data_get($context, 'request')->input('at'); // optionnel
        $when = $at ? Carbon::parse($at) : now();

        $reservation->status = 'checked_in';
        $reservation->actual_checkin = $when;
        $reservation->save();

        return $reservation->fresh(); // renvoie la ressource
    }
}
