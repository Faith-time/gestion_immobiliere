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
            border-left: 4px solid #007bff;
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
            color: #d9534f;
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
    <h3>Le Vendeur :</h3>
    <p><strong>{{ $vendeur->name }}</strong>, propriétaire du bien situé au {{ $bien->address }}, {{ $bien->city }}</p>
</div>

<div class="partie">
    <h3>L'Acheteur :</h3>
    <p><strong>{{ $acheteur->name }}</strong>, domicilié {{ $acheteur->email }}</p>
</div>

<div class="partie">
    <h3>L'Agence Immobilière :</h3>
    <p><strong>{{ $agence['nom'] }}</strong>, {{ $agence['adresse'] }}, {{ $agence['ville'] }}, représentée par {{ $agence['representant'] }}</p>
</div>

<div class="text-center" style="margin: 25px 0; font-weight: bold; font-size: 14px;">
    IL A ÉTÉ CONVENU CE QUI SUIT :
</div>

<div class="article">
    <div class="article-title">Article 1 – Désignation du bien</div>
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
    <div class="article-title">Article 2 – Prix de vente</div>
    <div class="article-content">
        Le prix est fixé à <span class="prix">{{ number_format($vente->prix_vente, 0, ',', ' ') }} FCFA</span>.
        @if($bien->mandat)
            Commission d'agence incluse : {{ number_format($bien->mandat->commission_fixe, 0, ',', ' ') }} FCFA.
        @endif
    </div>
</div>

<div class="article">
    <div class="article-title">Article 3 – Date de vente</div>
    <div class="article-content">
        La vente est effectuée en date du {{ \Carbon\Carbon::parse($vente->date_vente)->format('d/m/Y') }}.
    </div>
</div>

<div class="article">
    <div class="article-title">Article 4 – Paiement</div>
    <div class="article-content">
        Le paiement est effectué selon les modalités convenues entre les parties.
        La propriété sera transférée à la signature de l'acte authentique.
    </div>
</div>

<div class="article">
    <div class="article-title">Article 5 – Transfert de propriété</div>
    <div class="article-content">
        La propriété sera transférée à l'acheteur dès la signature du présent contrat et
        l'accomplissement de toutes les formalités administratives nécessaires.
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
                <div><strong>Le Vendeur</strong></div>
                <div class="signature-box">
                    @if(isset($vendeur_signature) && $vendeur_signature['is_signed'] && $vendeur_signature['data'])
                        <img src="{{ $vendeur_signature['data'] }}" alt="Signature vendeur" class="signature-image" />
                        <div class="signature-info">
                            {{ $vendeur->name }}<br>
                            <div class="signature-date">
                                Signé le {{ \Carbon\Carbon::parse($vendeur_signature['signed_at'])->format('d/m/Y à H:i') }}
                            </div>
                        </div>
                    @else
                        <div class="signature-placeholder">
                            Aucune signature<br>
                            {{ $vendeur->name }}
                        </div>
                    @endif
                </div>
            </td>
            <td>
                <div><strong>L'Acheteur</strong></div>
                <div class="signature-box">
                    @if(isset($acheteur_signature) && $acheteur_signature['is_signed'] && $acheteur_signature['data'])
                        <img src="{{ $acheteur_signature['data'] }}" alt="Signature acheteur" class="signature-image" />
                        <div class="signature-info">
                            {{ $acheteur->name }}<br>
                            <div class="signature-date">
                                Signé le {{ \Carbon\Carbon::parse($acheteur_signature['signed_at'])->format('d/m/Y à H:i') }}
                            </div>
                        </div>
                    @else
                        <div class="signature-placeholder">
                            Aucune signature<br>
                            {{ $acheteur->name }}
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
