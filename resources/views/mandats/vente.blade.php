{{-- resources/views/mandats/vente.blade.php - Version avec signatures --}}
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
        }
        .signature-image {
            max-width: 200px;
            max-height: 80px;
            margin: 10px auto;
            display: block;
        }
        .signature-placeholder {
            color: #999;
            font-style: italic;
            padding: 40px 10px;
            text-align: center;
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
            font-size: 10px;
            color: #666;
            margin-top: 5px;
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
    <h1>{{ $titre_mandat }}</h1>
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
    <div class="article-title">Article 3 – Prix de vente</div>
    <div class="article-content">
        Le prix de vente est fixé à <span class="prix">{{ number_format($bien->price, 0, ',', ' ') }} FCFA</span> net vendeur.
    </div>
</div>

<div class="article">
    <div class="article-title">Article 4 – Durée du mandat</div>
    <div class="article-content">
        Le présent mandat est conclu pour une durée de 12 mois
        à compter du {{ \Carbon\Carbon::parse($mandat->date_debut)->format('d/m/Y') }}
        jusqu'au {{ \Carbon\Carbon::parse($mandat->date_fin)->format('d/m/Y') }}.
    </div>
</div>

<div class="article">
    <div class="article-title">Article 5 – Rémunération</div>
    <div class="article-content">
        La commission du mandataire est de {{ $mandat->commission_pourcentage }}% TTC du prix de vente,
        soit <span class="prix">{{ number_format($mandat->commission_fixe, 0, ',', ' ') }} FCFA</span>,
        à la charge du vendeur.
    </div>
</div>

@if($mandat->type_mandat_vente)
    <div class="article">
        <div class="article-title">Article 6 – Type de mandat</div>
        <div class="article-content">
            Le présent mandat est un <strong>{{ $mandat->getTypeMandatLabel() }}</strong>.
        </div>
    </div>
@endif

<div class="date-lieu">
    Fait à {{ $ville_signature }}, le {{ $date_creation }}.
</div>
{{-- Section signatures CORRIGÉE --}}
<div class="signatures">
    <table>
        <tr>
            <td>
                <div><strong>Le Mandant</strong></div>
                <div class="signature-box">
                    @if(isset($proprietaire_signature) && $proprietaire_signature['is_signed'] && $proprietaire_signature['data'])
                        <img src="{{ $proprietaire_signature['data'] }}" alt="Signature propriétaire" class="signature-image" />
                        <div class="signature-info">
                            {{ $proprietaire->name }}<br>
                            <div class="signature-date">
                                Signé le {{ \Carbon\Carbon::parse($proprietaire_signature['signed_at'])->format('d/m/Y à H:i') }}
                            </div>
                        </div>
                    @else
                        <div class="signature-placeholder">
                            Aucune signature<br>
                            {{ $proprietaire->name }}
                        </div>
                    @endif
                </div>
            </td>
            <td>
                <div><strong>Le Mandataire</strong></div>
                <div class="signature-box">
                    @if(isset($agence_signature) && $agence_signature['is_signed'] && $agence_signature['data'])
                        <img src="{{ $agence_signature['data'] }}" alt="Signature agence" class="signature-image" />
                        <div class="signature-info">
                            {{ $agence['representant'] }}<br>
                            <small>{{ $agence['nom'] }}</small>
                            <div class="signature-date">
                                Signé le {{ \Carbon\Carbon::parse($agence_signature['signed_at'])->format('d/m/Y à H:i') }}
                            </div>
                        </div>
                    @else
                        <div class="signature-placeholder">
                            Aucune signature<br>
                            {{ $agence['representant'] }}<br>
                            <small>{{ $agence['nom'] }}</small>
                        </div>
                    @endif
                </div>
            </td>
        </tr>
    </table>
</div>

<style>
    .signature-image {
        max-width: 200px;
        max-height: 80px;
        object-fit: contain;
        display: block;
        margin: 10px auto;
        border: 1px solid #ddd;
        background: white;
        align-self: center;
    }

    .signature-box {
        border: 1px solid #ddd;
        min-height: 120px;
        position: relative;
        background: #fafafa;
        padding: 5px;
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
</style>
<div class="footer">
    <p>Document généré automatiquement le {{ now()->format('d/m/Y à H:i') }}</p>
    <p>{{ $agence['nom'] }} - {{ $agence['adresse'] }}, {{ $agence['ville'] }}</p>
    @if(isset($signature_status) && $signature_status === 'entierement_signe')
        <p><strong>Document certifié avec signatures électroniques horodatées</strong></p>
    @endif
</div>
</body>
</html>
