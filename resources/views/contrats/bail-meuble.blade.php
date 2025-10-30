<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bail de Location Meublée</title>
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
        .inventaire {
            background: #fff9db;
            padding: 10px;
            margin: 10px 0;
            border-left: 4px solid #ffc107;
        }
    </style>
</head>
<body>
<div class="header">
    <h1>BAIL DE LOCATION MEUBLÉE</h1>
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
    <div class="article-title">Article 1 – Objet du contrat</div>
    <div class="article-content">
        Le présent bail porte sur la location d'un logement meublé à usage d'habitation principale du locataire. Le logement est équipé de tous les meubles et équipements nécessaires à une occupation normale et conforme à la destination du bien.
    </div>
</div>

<div class="article">
    <div class="article-title">Article 2 – Désignation du logement</div>
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
    <div class="article-title">Article 3 – Inventaire du mobilier et des équipements</div>
    <div class="article-content">
        <div class="inventaire">
            <strong>Le logement comprend notamment les équipements suivants :</strong>
            <ul style="margin: 10px 0 0 20px;">
                <li>Literie complète avec couette et oreillers</li>
                <li>Mobilier de chambre (armoire, table de chevet)</li>
                <li>Mobilier de salon (canapé, table basse, étagères)</li>
                <li>Table et chaises pour les repas</li>
                <li>Cuisine équipée (plaques de cuisson, réfrigérateur, ustensiles)</li>
                <li>Vaisselle et couverts en nombre suffisant</li>
                <li>Luminaires dans toutes les pièces</li>
                <li>Volets ou rideaux occultants dans les chambres</li>
            </ul>
            <p style="margin-top: 10px;">
                Un inventaire détaillé contradictoire sera établi lors de l'état des lieux d'entrée et annexé au présent contrat.
            </p>
        </div>
    </div>
</div>

<div class="article">
    <div class="article-title">Article 4 – Durée du bail</div>
    <div class="article-content">
        Le bail est conclu pour une durée de <strong>1 an minimum</strong> (ou 9 mois pour les étudiants), du {{ \Carbon\Carbon::parse($location->date_debut)->format('d/m/Y') }}
        au {{ \Carbon\Carbon::parse($location->date_fin)->format('d/m/Y') }}. À l'échéance, le bail sera renouvelé par tacite reconduction pour une durée d'un an, sauf dénonciation par l'une des parties dans les conditions prévues par la loi.
    </div>
</div>

<div class="article">
    <div class="article-title">Article 5 – Loyer et charges</div>
    <div class="article-content">
        Le loyer mensuel charges comprises est fixé à <span class="prix">{{ number_format($location->loyer_mensuel, 0, ',', ' ') }} FCFA</span>.
        Le loyer est payable mensuellement à terme échu, le premier jour de chaque mois, par virement bancaire ou tout autre moyen convenu entre les parties.
    </div>
</div>

<div class="article">
    <div class="article-title">Article 6 – Dépôt de garantie</div>
    <div class="article-content">
        Un dépôt de garantie de <span class="prix">{{ number_format($location->loyer_mensuel * 2, 0, ',', ' ') }} FCFA</span> (équivalent à 2 mois de loyer) a été versé par le locataire lors de la signature du bail. Ce dépôt sera restitué au locataire dans un délai maximum de 2 mois après son départ, déduction faite le cas échéant des sommes restant dues, du coût des réparations locatives non effectuées et des éventuelles dégradations du mobilier.
    </div>
</div>

<div class="article">
    <div class="article-title">Article 7 – Usage des lieux et du mobilier</div>
    <div class="article-content">
        Le logement et le mobilier sont destinés exclusivement à l'habitation principale du locataire. Le locataire s'engage à :
        <ul style="margin-left: 20px; margin-top: 8px;">
            <li>Utiliser le mobilier et les équipements en bon père de famille</li>
            <li>Ne pas déplacer les meubles volumineux sans l'accord du bailleur</li>
            <li>Ne pas retirer d'équipements ou de mobilier du logement</li>
            <li>Signaler immédiatement tout dysfonctionnement ou dégradation</li>
            <li>Maintenir en bon état le mobilier et les équipements fournis</li>
        </ul>
        Toute sous-location, même partielle, est strictement interdite sans l'accord écrit et préalable du bailleur.
    </div>
</div>

<div class="article">
    <div class="article-title">Article 8 – Obligations du locataire</div>
    <div class="article-content">
        Le locataire s'engage à :
        <ul style="margin-left: 20px; margin-top: 8px;">
            <li>Payer le loyer aux termes convenus</li>
            <li>Entretenir le logement et le mobilier en bon état</li>
            <li>Effectuer les réparations locatives et l'entretien courant du mobilier</li>
            <li>Souscrire une assurance multirisque habitation couvrant les risques locatifs et le mobilier</li>
            <li>Respecter le règlement de copropriété et le règlement intérieur</li>
            <li>Ne pas troubler la jouissance des autres occupants</li>
            <li>Restituer les lieux et le mobilier en bon état à la fin du bail</li>
        </ul>
    </div>
</div>

<div class="article">
    <div class="article-title">Article 9 – Obligations du bailleur</div>
    <div class="article-content">
        Le bailleur s'engage à :
        <ul style="margin-left: 20px; margin-top: 8px;">
            <li>Délivrer un logement décent et meublé conformément à l'inventaire</li>
            <li>Assurer au locataire la jouissance paisible du logement</li>
            <li>Maintenir le mobilier et les équipements en bon état de fonctionnement</li>
            <li>Effectuer les réparations autres que locatives</li>
            <li>Remplacer le mobilier et les équipements devenus vétustes ou défectueux</li>
        </ul>
    </div>
</div>

<div class="article">
    <div class="article-title">Article 10 – Résiliation du bail</div>
    <div class="article-content">
        <strong>Par le locataire :</strong> Le locataire peut résilier le bail à tout moment en respectant un préavis d'<strong>1 mois</strong>. La notification doit être faite par lettre recommandée avec accusé de réception ou par acte d'huissier.<br><br>
        <strong>Par le bailleur :</strong> Le bailleur ne peut donner congé au locataire qu'en respectant un préavis de 3 mois et uniquement pour l'un des motifs légitimes et sérieux prévus par la loi (vente, reprise pour habiter, motif légitime et sérieux).
    </div>
</div>

<div class="article">
    <div class="article-title">Article 11 – État des lieux et inventaire</div>
    <div class="article-content">
        Un état des lieux contradictoire et un inventaire détaillé du mobilier seront établis lors de la remise des clés au locataire et lors de leur restitution. L'état des lieux de sortie et l'inventaire seront comparés à ceux d'entrée pour déterminer les éventuelles dégradations ou manquements imputables au locataire.
    </div>
</div>

<div class="article">
    <div class="article-title">Article 12 – Clause résolutoire</div>
    <div class="article-content">
        À défaut de paiement du loyer aux termes convenus, ou en cas de non-respect des obligations du locataire, et un mois après un commandement de payer demeuré infructueux, le présent bail sera résilié de plein droit si bon semble au bailleur.
    </div>
</div>

<div class="date-lieu">
    Fait à {{ $ville_signature }}, le {{ $date_creation }}, en deux exemplaires originaux.
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
