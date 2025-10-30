<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #2563eb; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9fafb; }
        .property-details { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #2563eb; }
        .btn { display: inline-block; padding: 12px 24px; background: #2563eb; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🎉 Bonne nouvelle !</h1>
    </div>

    <div class="content">
        <p>Bonjour {{ $dossier->client->name }},</p>

        <p>Nous avons trouvé un appartement qui correspond parfaitement à vos critères !</p>

        <div class="property-details">
            <h3>{{ $bien->title }}</h3>
            <p><strong>📍 Adresse :</strong> {{ $bien->address }}, {{ $bien->city }}</p>
            <p><strong>🏢 Étage :</strong> {{ $appartement->etage }}</p>
            <p><strong>🚪 Numéro :</strong> {{ $appartement->numero }}</p>
            <p><strong>📐 Superficie :</strong> {{ $appartement->superficie }} m²</p>

            <h4>🛋️ Composition :</h4>
            <ul>
                <li>Salons : {{ $appartement->salons }}</li>
                <li>Chambres : {{ $appartement->chambres }}</li>
                <li>Salles de bain : {{ $appartement->salles_bain }}</li>
                <li>Cuisines : {{ $appartement->cuisines }}</li>
            </ul>

            <p><strong>💰 Prix :</strong> {{ number_format($bien->price, 0, ',', ' ') }} FCFA</p>
        </div>

        <p>N'hésitez pas à nous contacter pour organiser une visite !</p>

        <a href="{{ route('biens.show', $bien->id) }}" class="btn">Voir le bien</a>
    </div>
</div>
</body>
</html>
