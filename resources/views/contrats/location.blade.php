<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $titre_contrat }}</title>
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
        .signature-box {
            border: 1px solid #ddd;
            min-height: 120px;
            position: relative;
            background: #fafafa;
            padding: 5px;
        }
        .signature-image {
            max-width: 200px;
            max-height: 80px;
            object-fit: contain;
            display: block;
            margin: 10px auto;
            border: 1px solid #ddd;
            background: white;
        }
        .signature-placeholder {
            color: #999;
            font-style: italic;
            padding: 40px 10px;
            text-align: center;
            line-height: 1.4;
        }
        .signature-info {
            position: absolute;
            bottom: 5px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        .signature-date {
            font-size: 9px;
            color: #666;
            margin-top: 3px;
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
        .signature-status {
            margin: 20px 0;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }
        .signature-status.fully-signed {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .signature-status.partially-signed {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
        }
        .signature-status.not-signed {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body>
<div class="header">
    <h1>{{ $titre_contrat }}</h1>
    @if(isset($signature_status))
        <div class="signature-status {{ $signature_status === 'entierement_signe' ? 'fully-signed' : ($signature_status === 'partiellement_signe' ? 'partially-signed' : 'not-signed') }}">
            @if($signature_status === 'entierement_signe')
                ✓ DOCUMENT ENTIÈREMENT SIGNÉ
            @elseif($signature_status === 'partiellement_signe')
                ⚠ DOCUMENT PARTIELLEMENT SIGNÉ
            @else
                ⚠ DOCUMENT NON SIGNÉ
            @endif
        </div>
    @endif
</div>

<div class="partie">
    <h3>Le Bailleur :</h3>
    <p><strong>{{ $bailleur->name }}</strong>, propriétaire du bien situé au {{ $bien->address }}, {{ $bien->city }}</p>
</div>

<div class="partie">
    <h3>Le Locataire :</h3>
    <p><strong>{{ $locataire->name }}</strong>, domicilié {{ $locataire->email }}</p>
</div>

<div class="partie">
    <h3>L'Agence Immobilière :</h3>
    <p><strong>{{ $agence['nom'] }}</strong>, {{ $agence['adresse'] }}, {{ $agence['ville'] }}, représentée par {{ $agence['representant'] }}</p>
</div>

<div class="text-center" style="margin: 25px 0; font-weight: bold; font-size: 14px;">
    IL A ÉTÉ CONVENU CE QUI SUIT :
</div>

<div class="article">
    <div class="article-title">Article 1 – Désignation du logement</div>
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
    <div class="article-title">Article 2 – Durée</div>
    <div class="article-content">
        Le bail est conclu du {{ \Carbon\Carbon::parse($location->date_debut)->format('d/m/Y') }}
        au {{ \Carbon\Carbon::parse($location->date_fin)->format('d/m/Y') }}.
    </div>
</div>

<div class="article">
    <div class="article-title">Article 3 – Loyer et charges</div>
    <div class="article-content">
        Le loyer mensuel est fixé à <span class="prix">{{ number_format($location->loyer_mensuel, 0, ',', ' ') }} FCFA</span> hors charges.
    </div>
</div>

<div class="article">
    <div class="article-title">Article 5 – Usage</div>
    <div class="article-content">
        Le logement est loué à usage d'habitation principale exclusivement.
        Toute sous-location est interdite sans accord préalable du bailleur.
    </div>
</div>

<div class="article">
    <div class="article-title">Article 6 – Obligations du locataire</div>
    <div class="article-content">
        Le locataire s'engage à :
        <ul style="margin-left: 20px; margin-top: 8px;">
            <li>Payer le loyer aux termes convenus</li>
            <li>Entretenir le logement en bon état</li>
            <li>Souscrire une assurance habitation</li>
            <li>Respecter le règlement de l'immeuble</li>
            <li>Permettre les visites nécessaires</li>
        </ul>
    </div>
</div>

<div class="article">
    <div class="article-title">Article 7 – Obligations du bailleur</div>
    <div class="article-content">
        Le bailleur s'engage à :
        <ul style="margin-left: 20px; margin-top: 8px;">
            <li>Délivrer le logement en bon état</li>
            <li>Assurer les réparations autres que locatives</li>
            <li>Garantir la jouissance paisible des lieux</li>
            <li>Respecter la réglementation en vigueur</li>
        </ul>
    </div>
</div>

<div class="date-lieu">
    Fait à {{ $ville_signature }}, le {{ $date_creation }}.
</div>

{{-- Section signatures --}}
<div class="signatures">
    <table>
        <tr>
            <td>
                <div><strong>Le Bailleur</strong></div>
                <div class="signature-box">
                    @if(isset($bailleur_signature) && $bailleur_signature['is_signed'] && $bailleur_signature['data'])
                        <img src="{{ $bailleur_signature['data'] }}" alt="Signature bailleur" class="signature-image" />
                        <div class="signature-info">
                            {{ $bailleur->name }}<br>
                            <div class="signature-date">
                                Signé le {{ \Carbon\Carbon::parse($bailleur_signature['signed_at'])->format('d/m/Y à H:i') }}
                            </div>
                        </div>
                    @else
                        <div class="signature-placeholder">
                            Aucune signature<br>
                            {{ $bailleur->name }}
                        </div>
                    @endif
                </div>
            </td>
            <td>
                <div><strong>Le Locataire</strong></div>
                <div class="signature-box">
                    @if(isset($locataire_signature) && $locataire_signature['is_signed'] && $locataire_signature['data'])
                        <img src="{{ $locataire_signature['data'] }}" alt="Signature locataire" class="signature-image" />
                        <div class="signature-info">
                            {{ $locataire->name }}<br>
                            <div class="signature-date">
                                Signé le {{ \Carbon\Carbon::parse($locataire_signature['signed_at'])->format('d/m/Y à H:i') }}
                            </div>
                        </div>
                    @else
                        <div class="signature-placeholder">
                            Aucune signature<br>
                            {{ $locataire->name }}
                        </div>
                    @endif
                </div>
            </td>
        </tr>
    </table>
</div>

<div class="footer">
    <p>Document généré automatiquement le {{ now()->format('d/m/Y à H:i') }}</p>
    <p>{{ $agence['nom'] }} - {{ $agence['adresse'] }}, {{ $agence['ville'] }}</p>
    <p>Téléphone: {{ $agence['telephone'] }} - Email: {{ $agence['email'] }}</p>
    @if(isset($signature_status) && $signature_status === 'entierement_signe')
        <p><strong>Document certifié avec signatures électroniques horodatées</strong></p>
    @endif
</div>
</body>
</html>
