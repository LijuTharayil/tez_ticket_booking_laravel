<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Str;

class TicketSeeder extends Seeder
{
    public function run()
    {
        $teams = [
            'Akcel United Brussels',
            'Antwerp Anchors',
            'Ghent Gladiators',
            'JB Bruges',
            'Liège Red Lions'
        ];

        $venues = [
            ['venue' => 'Brussels', 'stadium' => 'King Baudouin Stadium'],
            ['venue' => 'Antwerp', 'stadium' => 'Bosuilstadion'],
            ['venue' => 'Ghent', 'stadium' => 'Ghelamco Arena'],
            ['venue' => 'Bruges', 'stadium' => 'Jan Breydel Stadium'],
            ['venue' => 'Liège', 'stadium' => 'Stade Maurice Dufrasne'],
        ];

        $descriptions = [
            'A high-voltage clash between two top teams.',
            'An exciting cricket battle with star players.',
            'Witness an intense showdown under lights.',
            'A must-watch thriller between strong squads.',
            'Get ready for a nail-biting cricket match.',
        ];

        $startDate = Carbon::parse('2026-06-01');
        $endDate = Carbon::parse('2026-06-30');

        while ($startDate->lte($endDate)) {

            $team1 = $teams[array_rand($teams)];
            do {
                $team2 = $teams[array_rand($teams)];
            } while ($team1 === $team2);

            $venueData = $venues[array_rand($venues)];

            $matchTime = $startDate->copy()->setTime(rand(14, 20), 0); // between 2PM–8PM

            Ticket::create([
                'ticket_code' => 'TKT-' . strtoupper(Str::random(8)),
                'quantity' => rand(50, 200),
                'match_time' => $matchTime,
                'name' => 'Premium Match Ticket',
                'match_title' => $team1 . ' vs ' . $team2,
                'match_details' => $descriptions[array_rand($descriptions)],
                'venue' => $venueData['venue'],
                'stadium' => $venueData['stadium'],
                'image' => 'tickets/default.png',
                'ticket_rate_in_coin_quantity' => 10000,
            ]);

            $startDate->addDay();
        }
    }
}