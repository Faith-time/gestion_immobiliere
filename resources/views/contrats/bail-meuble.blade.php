<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrat de Location Meublée</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            line-height: 1.4;
            color: #000;
            font-size: 11px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }
        .header h1 {
            font-size: 16px;
            margin: 5px 0;
            text-transform: uppercase;
            font-weight: bold;
        }
        .header-info {
            font-size: 10px;
            margin: 3px 0;
        }
        .contrat-numero {
            text-align: right;
            margin-bottom: 15px;
            font-weight: bold;
        }
        .section {
            margin: 15px 0;
        }
        .section-title {
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 8px;
            font-size: 12px;
        }
        .partie-box {
            border: 1px solid #000;
            padding: 10px;
            margin: 10px 0;
            background: #f9f9f9;
        }
        .info-line {
            margin: 5px 0;
            display: flex;
            justify-content: space-between;
        }
        .info-label {
            font-weight: bold;
            width: 40%;
        }
        .info-value {
            width: 58%;
        }
        .article {
            margin: 12px 0;
        }
        .article-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .article-content {
            text-align: justify;
            margin-left: 10px;
        }
        .note-box {
            border: 2px solid #000;
            padding: 10px;
            margin: 15px 0;
            background: #ffffcc;
        }
        .note-box strong {
            text-decoration: underline;
        }
        .inventaire-box {
            border: 2px solid #000;
            padding: 10px;
            margin: 15px 0;
            background: #e8f4f8;
        }
        .signatures {
            margin-top: 30px;
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
            border: 1px solid #000;
            min-height: 100px;
            position: relative;
            background: #fff;
            padding: 5px;
        }
        .signature-image {
            max-width: 180px;
            max-height: 70px;
            object-fit: contain;
            display: block;
            margin: 10px auto;
        }
        .signature-placeholder {
            color: #666;
            font-style: italic;
            padding: 35px 10px;
            text-align: center;
        }
        .signature-info {
            position: absolute;
            bottom: 5px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
        }
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #000;
            font-size: 9px;
            text-align: center;
        }
        ul {
            margin: 5px 0;
            padding-left: 20px;
        }
        li {
            margin: 3px 0;
        }
    </style>
</head>
<body>
<div class="header">
    <h1>CAURIS IMMOBILIER</h1>
    <div class="header-info">RC : SN.DKR.2009.A.11649 - NINEA : 009017189</div>
    <div class="header-info">TEL : 77 448 32 28 / 77 516 72 28 / 76 785 98 48</div>
    <div class="header-info">EMAIL : caurisimmobiliere@gmail.com</div>
    <div class="header-info">ADRESSE : Keur Massar Rond Point Jaxaay P.A.U 14</div>
</div>

<div class="contrat-numero">
    Contrat N°: {{ 'C' . str_pad($location->id, 4, '0', STR_PAD_LEFT) }}
</div>

<h2 style="text-align: center; text-decoration: underline; margin: 20px 0;">
    BAIL DE LOCATION MEUBLÉE
</h2>

<div class="section-title" style="text-align: center; margin: 20px 0;">ENTRE LES SOUSSIGNÉS</div>

<div class="partie-box">
    <div style="font-weight: bold; margin-bottom: 10px;">LE BAILLEUR :</div>
    <div class="info-line">
        <span class="info-label">Nom et Prénoms :</span>
        <span class="info-value">{{ $bailleur->name }}</span>
    </div>
    <div class="info-line">
        <span class="info-label">Adresse :</span>
        <span class="info-value">{{ $bien->address }}, {{ $bien->city }}</span>
    </div>
    <div class="info-line">
        <span class="info-label">Téléphone :</span>
        <span class="info-value">{{ $bailleur->telephone ?? 'N/A' }}</span>
    </div>
</div>

<div style="text-align: center; margin: 15px 0; font-weight: bold;">ET D'AUTRE PART</div>

<div class="partie-box">
    <div style="font-weight: bold; margin-bottom: 10px;">M/Mme/Mlle {{ $locataire->name }}</div>
    @php
        $dossier = \App\Models\ClientDossier::where('client_id', $locataire->id)->first();
    @endphp
    <div class="info-line">
        <span class="info-label">Nom et Prénoms :</span>
        <span class="info-value">{{ $locataire->name }}</span>
    </div>
    <div class="info-line">
        <span class="info-label">Numéro CNI :</span>
        <span class="info-value">{{ $dossier->numero_cni ?? 'N/A' }}</span>
    </div>
    <div class="info-line">
        <span class="info-label">Situation Matrimoniale :</span>
        <span class="info-value">{{ $dossier ? ($dossier->situation_familiale == 'marie' ? 'Marié(e)' : 'Célibataire') : 'N/A' }}</span>
    </div>
    <div class="info-line">
        <span class="info-label">Téléphone :</span>
        <span class="info-value">{{ $locataire->telephone ?? 'N/A' }}</span>
    </div>
    <div class="info-line">
        <span class="info-label">Profession :</span>
        <span class="info-value">{{ $dossier->profession ?? 'N/A' }}</span>
    </div>
    @if($dossier && $dossier->derniere_quittance_path)
        <div class="info-line">
            <span class="info-label">Dernière quittance de loyer :</span>
            <span class="info-value">Fournie</span>
        </div>
    @endif
    @if($dossier && $dossier->personne_contact)
        <div class="info-line">
            <span class="info-label">En cas de force majeure, contacter :</span>
            <span class="info-value">{{ $dossier->personne_contact }}</span>
        </div>
        <div class="info-line">
            <span class="info-label">Téléphone :</span>
            <span class="info-value">{{ $dossier->telephone_contact }}</span>
        </div>
    @endif
</div>

<div style="text-align: center; margin: 20px 0; font-weight: bold;">
    IL EST CONVENU ET ARRÊTÉ CE QUI SUIT
</div>

<div class="section">
    <div class="article-title">Article 1 : OBJET DU CONTRAT</div>
    <div class="article-content">
        Le présent bail porte sur la location d'un <strong>logement meublé</strong> à usage d'habitation principale du
        locataire. Le logement est équipé de tous les meubles et équipements nécessaires à une occupation normale et
        conforme à la destination du bien.
    </div>
</div>

<div class="section">
    <div class="article-title">Article 2 : DÉSIGNATION DU LOGEMENT</div>
    <div class="article-content">
        <strong>{{ $bien->title }}</strong><br>
        Situé à : {{ $bien->address }}, {{ $bien->city }}<br>
        Superficie : {{ number_format($bien->superficy, 0, ',', ' ') }} m²<br>
        Composition : {{ $bien->rooms }} chambre(s), {{ $bien->living_rooms }} salon(s),
        {{ $bien->kitchens }} cuisine(s), {{ $bien->bathrooms }} salle(s) de bain<br>
        @if($bien->description)
            Description : {{ $bien->description }}
        @endif
    </div>
</div>

<div class="inventaire-box">
    <div style="font-weight: bold; margin-bottom: 10px; text-decoration: underline;">
        Article 3 : INVENTAIRE DU MOBILIER ET DES ÉQUIPEMENTS
    </div>
    <div style="margin-bottom: 10px;">
        <strong>Le logement meublé comprend notamment les équipements suivants :</strong>
    </div>
    <ul>
        <li>Literie complète avec couette et oreillers</li>
        <li>Mobilier de chambre (armoire, table de chevet)</li>
        <li>Mobilier de salon (canapé, table basse, étagères)</li>
        <li>Table et chaises pour les repas</li>
        <li>Cuisine équipée (plaques de cuisson, réfrigérateur, ustensiles)</li>
        <li>Vaisselle et couverts en nombre suffisant</li>
        <li>Luminaires dans toutes les pièces</li>
        <li>Volets ou rideaux occultants dans les chambres</li>
        <li>Équipements sanitaires complets</li>
    </ul>
    <div style="margin-top: 10px;">
        <strong>Un inventaire détaillé contradictoire sera établi lors de l'état des lieux d'entrée et annexé au
            présent contrat.</strong>
    </div>
</div>

<div class="section">
    <div class="article-title">I. LOYER</div>
    <div class="article-content">
        Le loyer mensuel charges comprises est fixé à <strong>{{ number_format($location->loyer_mensuel, 0, ',', ' ') }} FCFA</strong>,
        payable par terme à échoir et d'avance au plus tard le <strong>05 de chaque mois</strong>.<br><br>
        Une pénalité de <strong>cinq pour cent (5%)</strong> sur le loyer sera versée par le preneur en cas de retard
        de paiement c'est à dire après le 05 de chaque mois.<br><br>
        Tout mois entamé par le preneur est dû dans son intégralité.<br><br>
        Le loyer est portable et non quérable dans les bureaux de Cauris Immo : sis à Keur Massar/Jaxaay-Parcelles
        unité 14 Villa N° 31 à l'angle Ouest du grand Rond-point.
    </div>
</div>

<div class="section">
    <div class="article-title">II. CAUTION</div>
    <div class="article-content">
        A la signature du présent contrat, une caution de garantie équivalente à <strong>01 (un) mois</strong>
        et <strong>01 mois d'avance de loyer</strong> seront versés par le preneur au bailleur,
        soit un montant total de <strong>{{ number_format($location->loyer_mensuel * 2, 0, ',', ' ') }} FCFA</strong>.
    </div>
</div>

<div class="note-box">
    <strong>NB :</strong><br>
    **La caution ne sera restituée au locataire qu'après le règlement de tous les arriérés de loyer et la remise
    en état des lieux et du mobilier.<br><br>
    La caution couvre les garanties suivantes :<br>
    - Eventuelles dégradations du bien immobilier et du mobilier<br>
    - Eventuels arriérés de loyer<br>
    - Remplacement du mobilier endommagé ou manquant<br>
    - Production des comptes clôturés des abonnements SENELEC, SONATEL et SDE<br><br>
    A défaut il sera prélevé sur ladite caution les sommes correspondantes aux frais de remise en état des lieux
    et du mobilier, ainsi que le montant des factures d'eau, d'électricité et de téléphone non réglé.
</div>

<div class="section">
    <div class="article-title">III. DURÉE</div>
    <div class="article-content">
        Le présent bail meublé de courte durée commence à compter du
        <strong>{{ \Carbon\Carbon::parse($location->date_debut)->format('d/m/Y') }}</strong>
        et est consenti pour une période de <strong>{{ \Carbon\Carbon::parse($location->date_debut)->diffInMonths($location->date_fin) }} mois</strong>,
        soit jusqu'au <strong>{{ \Carbon\Carbon::parse($location->date_fin)->format('d/m/Y') }}</strong>.<br><br>
        <em>Durée typique pour un bail meublé : de 3 jours à 3 mois maximum.</em>
    </div>
</div>

<div class="section">
    <div class="article-title">IV. USAGE DES LIEUX ET DU MOBILIER</div>
    <div class="article-content">
        Le logement et le mobilier sont destinés exclusivement à l'habitation principale du locataire. Le locataire
        s'engage à :<br>
        <ul>
            <li>Utiliser le mobilier et les équipements en bon père de famille</li>
            <li>Ne pas déplacer les meubles volumineux sans l'accord du bailleur</li>
            <li>Ne pas retirer d'équipements ou de mobilier du logement</li>
            <li>Signaler immédiatement tout dysfonctionnement ou dégradation</li>
            <li>Maintenir en bon état le mobilier et les équipements fournis</li>
            <li>Ne pas sous-louer, même partiellement, sans accord écrit du bailleur</li>
        </ul>
    </div>
</div>

<div class="article">
    <div class="article-title">Article 1 : OBLIGATIONS DU LOCATAIRE</div>
    <div class="article-content">
        1/ Le locataire reconnaît prendre les lieux et le mobilier en bon état et s'engage à les restituer en parfait
        état d'entretien.<br><br>
        2/ Le locataire veille à ne pas troubler la jouissance paisible des voisins.<br><br>
        3/ Le locataire doit souscrire une assurance multirisque habitation couvrant les risques locatifs et le mobilier.<br><br>
        4/ Aucune modification du mobilier ou des équipements ne peut être effectuée sans autorisation préalable écrite
        du bailleur.
    </div>
</div>

<div class="article">
    <div class="article-title">Article 2 : OBLIGATIONS DU BAILLEUR</div>
    <div class="article-content">
        Le bailleur s'engage à :<br>
        <ul>
            <li>Délivrer un logement décent et meublé conformément à l'inventaire</li>
            <li>Assurer la jouissance paisible du logement</li>
            <li>Maintenir le mobilier et les équipements en bon état de fonctionnement</li>
            <li>Remplacer le mobilier et les équipements devenus vétustes ou défectueux</li>
        </ul>
    </div>
</div>

<div class="article">
    <div class="article-title">Article 3 : CHARGES LOCATIVES</div>
    <div class="article-content">
        Les consommations d'eau, d'électricité, et de téléphone sont incluses dans le loyer ou à la charge du locataire
        selon les termes convenus.
    </div>
</div>

<div class="article">
    <div class="article-title">Article 4 : PRÉAVIS</div>
    <div class="article-content">
        Pour un bail meublé de courte durée, le préavis est de <strong>1 mois</strong> pour le locataire.
        La notification doit être faite par lettre recommandée avec accusé de réception ou par acte d'huissier.
    </div>
</div>

<div class="article">
    <div class="article-title">Article 5 : ÉTAT DES LIEUX ET INVENTAIRE</div>
    <div class="article-content">
        Un état des lieux contradictoire et un inventaire détaillé du mobilier seront établis lors de la remise des
        clés au locataire et lors de leur restitution. L'état des lieux de sortie et l'inventaire seront comparés à
        ceux d'entrée pour déterminer les éventuelles dégradations ou manquements imputables au locataire.
    </div>
</div>

<div class="section">
    <div class="article-title">V. CLAUSES RÉSOLUTOIRES</div>
    <div class="article-content">
        A défaut du paiement du loyer aux termes convenus, ou en cas de non-respect des obligations du locataire,
        et <strong>trente (30) jours</strong> après une mise en demeure par lettre recommandée demeurée sans effet,
        le bail sera résilié de plein droit si bon semble au bailleur.<br><br>
        En cas d'occupation après cessation du bail, une indemnité égale au <strong>double du loyer</strong> sera due.<br><br>
        Tous les frais de justice supportés du fait du manquement du locataire seront remboursés par celui-ci.
    </div>
</div>

<div class="section">
    <div class="article-title">VI. IMPÔTS ET TAXES</div>
    <div class="article-content">
        Les frais d'enregistrement et de timbres sont à la charge exclusive du preneur ainsi que toutes les taxes liées
        à l'occupation des lieux.
    </div>
</div>

<div style="margin: 30px 0; text-align: right;">
    <strong>Fait à Dakar, Keur Massar le {{ \Carbon\Carbon::parse($location->created_at)->format('d/m/Y') }}</strong>
</div>

<div style="margin: 20px 0; text-align: center; font-weight: bold;">
    Lu et approuvés par les deux parties,
</div>

<div class="signatures">
    <table>
        <tr>
            <td>
                <div><strong>LE BAILLEUR</strong></div>
                <div class="signature-box">
                    @if(isset($bailleur_signature) && $bailleur_signature['is_signed'] && $bailleur_signature['data'])
                        <img src="{{ $bailleur_signature['data'] }}" alt="Signature bailleur" class="signature-image" />
                        <div class="signature-info">
                            {{ $bailleur->name }}<br>
                            Signé le {{ \Carbon\Carbon::parse($bailleur_signature['signed_at'])->format('d/m/Y à H:i') }}
                        </div>
                    @else
                        <div class="signature-placeholder">
                            Signature à apposer<br>
                            {{ $bailleur->name }}
                        </div>
                    @endif
                </div>
            </td>
            <td>
                <div><strong>LE LOCATAIRE</strong></div>
                <div class="signature-box">
                    @if(isset($locataire_signature) && $locataire_signature['is_signed'] && $locataire_signature['data'])
                        <img src="{{ $locataire_signature['data'] }}" alt="Signature locataire" class="signature-image" />
                        <div class="signature-info">
                            {{ $locataire->name }}<br>
                            Signé le {{ \Carbon\Carbon::parse($locataire_signature['signed_at'])->format('d/m/Y à H:i') }}
                        </div>
                    @else
                        <div class="signature-placeholder">
                            Signature à apposer<br>
                            {{ $locataire->name }}
                        </div>
                    @endif
                </div>
            </td>
        </tr>
    </table>
</div>

<div style="margin-top: 20px; padding: 10px; border: 1px solid #000; font-size: 10px;">
    <strong>Pièces Jointes :</strong><br>
    - Fiche État des lieux d'entrée<br>
    - Inventaire détaillé du mobilier
</div>

<div class="footer">
    Document généré le {{ now()->format('d/m/Y à H:i') }}<br>
    CAURIS IMMOBILIER - Keur Massar Rond Point Jaxaay P.A.U 14<br>
    Tel: 77 448 32 28 / 77 516 72 28 / 76 785 98 48 - Email: caurisimmobiliere@gmail.com
    @if(isset($signature_status) && $signature_status === 'entierement_signe')
        <br><strong>Document certifié avec signatures électroniques horodatées</strong>
    @endif
</div>
</body>
</html>
