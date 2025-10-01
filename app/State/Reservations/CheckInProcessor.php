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
        $reservation = $data;

        if (!$reservation->canCheckIn()) {
            abort(422, 'Check-in non autorisé pour cet état ou déjà effectué.');
        }

        $at = data_get($context, 'request')->input('at');
        $when = $at ? Carbon::parse($at) : now();

        $reservation->status = 'checked_in';
        $reservation->actual_checkin = $when;
        $reservation->save();

        // ⬅️ Masquer les relations sans API
        return $reservation->fresh()->makeHidden(['customer', 'product']);
    }
}
