@component('mail::message')
    # Rappel de Paiement de Loyer

    Bonjour {{ $location->client->name }},

    Ceci est un rappel amical concernant le paiement de votre loyer.

    **Détails du loyer :**
    - Bien loué : {{ $location->bien->title }}
    - Adresse : {{ $location->bien->address }}, {{ $location->bien->city }}
    - Montant : {{ number_format($location->loyer_mensuel, 0, ',', ' ') }} FCFA
    - Date d'échéance : {{ $dateEcheance->format('d/m/Y') }}

    @component('mail::button', ['url' => route('locations.show', $location->id)])
        Effectuer le paiement
    @endcomponent

    Merci de régler votre loyer dans les temps pour éviter les pénalités de retard.

    Cordialement,<br>
    {{ config('app.name') }}
@endcomponent
