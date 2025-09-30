<?php

namespace App\State\Reservations;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Models\Reservation;
use Carbon\Carbon;

class CheckOutProcessor implements ProcessorInterface
{
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var Reservation $reservation */
        $reservation = $data;

        if (!$reservation->canCheckOut()) {
            abort(422, 'Check-out non autorisé pour cet état ou déjà effectué.');
        }

        $at = data_get($context, 'request')->input('at'); // optionnel
        $when = $at ? Carbon::parse($at) : now();

        if ($reservation->actual_checkin && $when->lt($reservation->actual_checkin)) {
            abort(422, 'Impossible de faire check-out avant le check-in.');
        }

        $reservation->status = 'checked_out';
        $reservation->actual_checkout = $when;
        $reservation->save();

        return $reservation->fresh();
    }
}
