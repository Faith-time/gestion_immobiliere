{{-- resources/views/mandats/gerance.blade.php --}}
    <!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $titre_mandat }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            line-height: 1.6;
            color: #333;
            font-size: 12px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 18px;
            margin: 0;
            text-transform: uppercase;
            font-weight: bold;
        }
        .partie {
            margin: 20px 0;
            padding: 10px;
            background: #f8f9fa;
            border-left: 4px solid #28a745;
        }
        .partie h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            font-weight: bold;
        }
        .article {
            margin: 20px 0;
        }
        .article-title {
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 8px;
        }
        .article-content {
            margin-left: 15px;
            text-align: justify;
        }
        .bien-details {
            background: #f0f0f0;
            padding: 10px;
            margin: 8px 0;
            border-radius: 3px;
        }
        .prix {
            font-weight: bold;
            color: #28a745;
        }
        .signatures {
            margin-top: 40px;
            width: 100%;
        }
        .signatures table {
            width: 100%;
            border-collapse: collapse;
        }
        .signatures td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 10px;
        }
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 50px;
            padding-top: 8px;
            font-size: 11px;
        }
        .date-lieu {
            text-align: right;
            margin: 25px 0;
            font-style: italic;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ccc;
            font-size: 10px;
            color: #666;
            text-align: center;
        }
        .text-center {
            text-align: center;
        }
        .font-bold {
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="header">
    <h1>{{ $titre_mandat }}</h1>
</div>

<div class="partie">
    <h3>Le Mandant :</h3>
    <p><strong>{{ $proprietaire->name }}</strong>, propriétaire du bien situé au {{ $bien->address }}, {{ $bien->city }}</p>
</div>

<div class="partie">
    <h3>Et Le Mandataire :</h3>
    <p><strong>{{ $agence['nom'] }}</strong>, {{ $agence['adresse'] }}, {{ $agence['ville'] }}, représentée par {{ $agence['representant'] }}</p>
</div>

<div class="text-center" style="margin: 25px 0; font-weight: bold; font-size: 14px;">
    IL A ÉTÉ CONVENU CE QUI SUIT :
</div>

<div class="article">
    <div class="article-title">Article 1 – Objet du mandat</div>
    <div class="article-content">
        {{ $objet }}
    </div>
</div>

<div class="article">
    <div class="article-title">Article 2 – Désignation du bien</div>
    <div class="article-content">
        <div class="bien-details">
            <strong>{{ $bien->title }}</strong><br>
            Superficie : {{ number_format($bien->superficy, 0, ',', ' ') }} m²<br>
            Nombre de pièces : {{ $bien->rooms }}<br>
            @if($bien->bathrooms) Nombre de salles de bains : {{ $bien->bathrooms }}<br> @endif
            @if($bien->floors) Nombre d'étages : {{ $bien->floors }}<br> @endif
            Adresse : {{ $bien->address }}, {{ $bien->city }}<br>
            @if($bien->description) Description : {{ $bien->description }} @endif
        </div>
    </div>
</div>

<div class="article">
    <div class="article-title">Article 3 – Loyer et charges</div>
    <div class="article-content">
        Le loyer mensuel est fixé à <span class="prix">{{ number_format($bien->price, 0, ',', ' ') }} FCFA</span> hors charges.
    </div>
</div>

<div class="article">
    <div class="article-title">Article 4 – Durée du mandat</div>
    <div class="article-content">
        Le présent mandat de gérance est conclu pour une durée de 12 mois
        à compter du {{ \Carbon\Carbon::parse($mandat->date_debut)->format('d/m/Y') }}
        jusqu'au {{ \Carbon\Carbon::parse($mandat->date_fin)->format('d/m/Y') }}.
    </div>
</div>

<div class="article">
    <div class="article-title">Article 5 – Rémunération</div>
    <div class="article-content">
        La commission de gérance du mandataire est de {{ $mandat->commission_pourcentage }}% TTC du loyer mensuel,
        soit <span class="prix">{{ number_format($mandat->commission_fixe, 0, ',', ' ') }} FCFA</span> par mois.
    </div>
</div>

<div class="article">
    <div class="article-title">Article 6 – Obligations du mandataire</div>
    <div class="article-content">
        Le mandataire s'engage à :
        <ul style="margin-left: 20px; margin-top: 8px;">
            <li>Rechercher des locataires solvables</li>
            <li>Encaisser les loyers et charges</li>
            <li>Suivre les éventuels impayés</li>
            <li>Effectuer les réparations courantes</li>
            <li>Tenir une comptabilité détaillée</li>
            <li>Rendre compte périodiquement au propriétaire</li>
        </ul>
    </div>
</div>

<div class="article">
    <div class="article-title">Article 7 – Obligations du mandant</div>
    <div class="article-content">
        Le mandant s'engage à :
        <ul style="margin-left: 20px; margin-top: 8px;">
            <li>Fournir tous les documents nécessaires</li>
            <li>Assurer le bien contre les risques locatifs</li>
            <li>Prendre en charge les grosses réparations</li>
            <li>Respecter la réglementation en vigueur</li>
        </ul>
    </div>
</div>

<div class="date-lieu">
    Fait à {{ $ville_signature }}, le {{ $date_creation }}.
</div>

<div class="signatures">
    <table>
        <tr>
            <td>
                <div><strong>Le Mandant</strong></div>
                <div class="signature-line">
                    {{ $proprietaire->name }}<br>
                    <small>Signature du propriétaire</small>
                </div>
            </td>
            <td>
                <div><strong>Le Mandataire</strong></div>
                <div class="signature-line">
                    {{ $agence['representant'] }}<br>
                    <small>{{ $agence['nom'] }}</small>
                </div>
            </td>
        </tr>
    </table>
</div>

<div class="footer">
    <p>Document généré automatiquement le {{ now()->format('d/m/Y à H:i') }}</p>
    <p>{{ $agence['nom'] }} - {{ $agence['adresse'] }}, {{ $agence['ville'] }}</p>
</div>
</body>
</html>
