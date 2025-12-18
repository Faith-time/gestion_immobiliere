<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Quittance de Loyer N°{{ $numero_quittance }}</title>
    <style>
        @page {
            margin: 15px;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            color: #000;
            line-height: 1.3;
            margin: 0;
            padding: 0;
        }

        .container {
            border: 2px solid #000;
            padding: 15px;
            margin: 0 auto;
        }

        /* En-tête entreprise */
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .header .company-name {
            font-size: 16px;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
        }

        .header .tagline {
            font-size: 11px;
            margin: 3px 0;
        }

        .header .info {
            font-size: 9px;
            margin: 2px 0;
        }

        /* Titre quittance */
        .title-section {
            text-align: center;
            margin: 15px 0;
        }

        .title-section h2 {
            font-size: 14px;
            font-weight: bold;
            margin: 5px 0;
            text-decoration: underline;
        }

        .matricule-box {
            float: right;
            border: 1px solid #000;
            padding: 5px 10px;
            margin-top: -40px;
            background: #fff;
        }

        .matricule-box strong {
            font-size: 11px;
        }

        /* Tableau des montants */
        .amounts-table {
            width: 100%;
            margin: 20px 0;
            font-size: 10px;
        }

        .amounts-table td {
            padding: 6px 5px;
            vertical-align: top;
        }

        .amounts-table .label {
            width: 35%;
            font-weight: normal;
        }

        .amounts-table .value {
            width: 15%;
            text-align: right;
            font-weight: bold;
        }

        .amounts-table .total-row {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            font-weight: bold;
            font-size: 11px;
        }

        .amounts-table .separator-row {
            height: 10px;
        }

        /* Section reçu */
        .receipt-section {
            margin: 20px 0;
            font-size: 10px;
            line-height: 1.6;
        }

        .receipt-section p {
            margin: 5px 0;
        }

        .receipt-section .amount {
            font-weight: bold;
            text-decoration: underline;
        }

        .receipt-section .tenant-name,
        .receipt-section .owner-name {
            font-weight: bold;
        }

        /* Section notes */
        .notes-section {
            margin: 25px 0 15px 0;
            font-size: 9px;
            line-height: 1.5;
        }

        .notes-section .location-date {
            text-align: right;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .notes-section .nota-title {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 5px;
        }

        .notes-section ol {
            margin: 5px 0 5px 20px;
            padding: 0;
        }

        .notes-section li {
            margin: 3px 0;
        }

        /* Section alerte retard */
        .warning-box {
            background: #ffe6e6;
            border: 2px solid #ff0000;
            padding: 10px;
            margin: 15px 0;
            font-size: 10px;
        }

        .warning-box strong {
            color: #ff0000;
        }

        /* Clear float */
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }

        /* Séparateur de page pour les 2 copies */
        .page-break {
            page-break-after: always;
            margin-bottom: 30px;
            border-bottom: 3px dashed #000;
            padding-bottom: 30px;
        }
    </style>
</head>
<body>
<!-- COPIE LOCATAIRE -->
<div class="container page-break">
    <div class="header">
        <p class="company-name">CAURIS IMMOBILIER</p>
        <p class="tagline">Toutes transactions Immobilières<br>Gérance-Location</p>
        <p class="info">RC : SN.DKR.2009.A.11649</p>
        <p class="info">NINEA : 009017189</p>
        <p class="info">TEL : 77 448 32 28 / 77 516 72 28 / 76 785 98 48</p>
        <p class="info">EMAIL : jacobleyla@hotmail.fr</p>
        <p class="info">ADRESSE : Keur Massar Rond Point Jaxaay P.A.U 14</p>
    </div>

    <div class="clearfix">
        <div class="title-section">
            <h2>QUITTANCE DE LOYER N°{{ $numero_quittance }}</h2>
        </div>
        <div class="matricule-box">
            <strong>Matricule {{ $matricule }}</strong>
        </div>
    </div>

    <table class="amounts-table">
        @if($depot_garantie_reservation > 0)
            <tr>
                <td class="label">Dépôt de garantie (réservation):</td>
                <td class="value">{{ number_format($depot_garantie_reservation, 0, ',', ' ') }}</td>
            </tr>
        @endif

        @if($caution > 0)
            <tr>
                <td class="label">Caution:</td>
                <td class="value">{{ number_format($caution, 0, ',', ' ') }}</td>
            </tr>
        @endif

        <tr>
            <td class="label">Loyer:</td>
            <td class="value">{{ number_format($loyer, 0, ',', ' ') }}</td>
        </tr>

        <tr>
            <td class="label">Arriérés:</td>
            <td class="value">{{ $arrieres > 0 ? number_format($arrieres, 0, ',', ' ') : '-' }}</td>
        </tr>

        <tr>
            <td class="label">Pénalités:</td>
            <td class="value">{{ $penalites > 0 ? number_format($penalites, 0, ',', ' ') : '-' }}</td>
        </tr>

        <tr>
            <td class="label">Avance:</td>
            <td class="value">{{ $avance > 0 ? number_format($avance, 0, ',', ' ') : '-' }}</td>
        </tr>

        <tr>
            <td class="label">Commission Agence:</td>
            <td class="value">{{ $commission > 0 ? number_format($commission, 0, ',', ' ') : '-' }}</td>
        </tr>

        <tr>
            <td class="label">TOM:</td>
            <td class="value">{{ $tom > 0 ? number_format($tom, 0, ',', ' ') : '-' }}</td>
        </tr>

        <tr>
            <td class="label">Charges:</td>
            <td class="value">{{ $charges > 0 ? number_format($charges, 0, ',', ' ') : '-' }}</td>
        </tr>

        <tr>
            <td class="label">Taxe T.V.A:</td>
            <td class="value">{{ $taxe_tva > 0 ? number_format($taxe_tva, 0, ',', ' ') : '-' }}</td>
        </tr>

        <tr>
            <td class="label">Frais de Justice:</td>
            <td class="value">{{ $frais_justice > 0 ? number_format($frais_justice, 0, ',', ' ') : '-' }}</td>
        </tr>

        <tr>
            <td class="label">Provision EAU:</td>
            <td class="value">{{ $provision_eau > 0 ? number_format($provision_eau, 0, ',', ' ') : '-' }}</td>
        </tr>

        <tr>
            <td class="label">Provision Elect:</td>
            <td class="value">{{ $provision_elect > 0 ? number_format($provision_elect, 0, ',', ' ') : '-' }}</td>
        </tr>

        <tr>
            <td class="label">Reliquat:</td>
            <td class="value">{{ $reliquat > 0 ? number_format($reliquat, 0, ',', ' ') : '-' }}</td>
        </tr>

        <tr class="separator-row">
            <td colspan="2"></td>
        </tr>

        <tr class="total-row">
            <td class="label">TOTAL:</td>
            <td class="value">{{ number_format($total, 0, ',', ' ') }}</td>
        </tr>
    </table>

    @if($jours_retard > 0)
        <div class="warning-box">
            <strong>⚠️ PAIEMENT EN RETARD</strong><br>
            Votre paiement a été effectué avec <strong>{{ $jours_retard }} jour(s) de retard</strong>.<br>
            Pénalités appliquées : <strong>{{ number_format($penalites, 0, ',', ' ') }} FCFA</strong>
        </div>
    @endif

    <div class="receipt-section">
        <p><strong>Reçu de M./Mme <span class="tenant-name">{{ strtoupper($locataire['nom']) }}</span></strong></p>
        <p>La somme de <span class="amount">{{ number_format($montant_paye, 0, ',', ' ') }} FCFA ({{ $montant_lettre }})</span></p>
        <p><strong>Nature de la transaction : {{ strtoupper($type_paiement ?? 'LOYER ' . strtoupper($mois_concerne)) }}</strong></p>
        <p>des locaux qu'il occupe chez : <span class="owner-name">{{ strtoupper($proprietaire['nom']) }}</span></p>
        @if($appartement)
            <p><strong>Appartement N°{{ $appartement['numero'] }} - Étage {{ $appartement['etage'] }}</strong></p>
        @endif
        <p><strong>Adresse :</strong> {{ $bien['adresse'] }}, {{ $bien['ville'] }}</p>
    </div>

    <div class="notes-section">
        <p class="location-date">Fait à Dakar, le {{ $date_paiement }}</p>

        <p class="nota-title">NOTA: Aucun locataire ne peut déménager sans:</p>
        <ol>
            <li>Qu'il n'ait donné ou reçu congé par écrit dans les délais</li>
            <li>Qu'il n'ait acquitté de ses consommations d'Eau, d'Électricité</li>
            <li>Qu'il n'ait acquitté de ses Impôts et taxes</li>
            <li>Il ne peut céder ni sous louer sans l'accord écrit du propriétaire</li>
            <li>Chaque jour de retard de paiement occasionne 5% de pénalité sur le loyer</li>
        </ol>
    </div>
</div>

<!-- COPIE BAILLEUR -->
<div class="container">
    <div class="header">
        <p class="company-name">CAURIS IMMOBILIER</p>
        <p class="tagline">Toutes transactions Immobilières<br>Gérance-Location</p>
        <p class="info">RC : SN.DKR.2009.A.11649</p>
        <p class="info">NINEA : 009017189</p>
        <p class="info">TEL : 77 448 32 28 / 77 516 72 28 / 76 785 98 48</p>
        <p class="info">EMAIL : jacobleyla@hotmail.fr</p>
        <p class="info">ADRESSE : Keur Massar Rond Point Jaxaay P.A.U 14</p>
    </div>

    <div class="clearfix">
        <div class="title-section">
            <h2>QUITTANCE BAILLEUR N°{{ $numero_quittance }}</h2>
        </div>
        <div class="matricule-box">
            <strong>Matricule {{ $matricule }}</strong>
        </div>
    </div>

    <table class="amounts-table">
        @if($depot_garantie_reservation > 0)
            <tr>
                <td class="label">Dépôt de garantie (réservation):</td>
                <td class="value">{{ number_format($depot_garantie_reservation, 0, ',', ' ') }}</td>
            </tr>
        @endif

        @if($caution > 0)
            <tr>
                <td class="label">Caution:</td>
                <td class="value">{{ number_format($caution, 0, ',', ' ') }}</td>
            </tr>
        @endif

        <tr>
            <td class="label">Loyer:</td>
            <td class="value">{{ number_format($loyer, 0, ',', ' ') }}</td>
        </tr>

        <tr>
            <td class="label">Arriérés:</td>
            <td class="value">{{ $arrieres > 0 ? number_format($arrieres, 0, ',', ' ') : '-' }}</td>
        </tr>

        <tr>
            <td class="label">Pénalités:</td>
            <td class="value">{{ $penalites > 0 ? number_format($penalites, 0, ',', ' ') : '-' }}</td>
        </tr>

        <tr>
            <td class="label">Avance:</td>
            <td class="value">{{ $avance > 0 ? number_format($avance, 0, ',', ' ') : '-' }}</td>
        </tr>

        <tr>
            <td class="label">Commission Agence:</td>
            <td class="value">{{ $commission > 0 ? number_format($commission, 0, ',', ' ') : '-' }}</td>
        </tr>

        <tr>
            <td class="label">TOM:</td>
            <td class="value">{{ $tom > 0 ? number_format($tom, 0, ',', ' ') : '-' }}</td>
        </tr>

        <tr>
            <td class="label">Charges:</td>
            <td class="value">{{ $charges > 0 ? number_format($charges, 0, ',', ' ') : '-' }}</td>
        </tr>

        <tr>
            <td class="label">Taxe T.V.A:</td>
            <td class="value">{{ $taxe_tva > 0 ? number_format($taxe_tva, 0, ',', ' ') : '-' }}</td>
        </tr>

        <tr>
            <td class="label">Frais de Justice:</td>
            <td class="value">{{ $frais_justice > 0 ? number_format($frais_justice, 0, ',', ' ') : '-' }}</td>
        </tr>

        <tr>
            <td class="label">Provision EAU:</td>
            <td class="value">{{ $provision_eau > 0 ? number_format($provision_eau, 0, ',', ' ') : '-' }}</td>
        </tr>

        <tr>
            <td class="label">Provision Elect:</td>
            <td class="value">{{ $provision_elect > 0 ? number_format($provision_elect, 0, ',', ' ') : '-' }}</td>
        </tr>

        <tr>
            <td class="label">Reliquat:</td>
            <td class="value">{{ $reliquat > 0 ? number_format($reliquat, 0, ',', ' ') : '-' }}</td>
        </tr>

        <tr class="separator-row">
            <td colspan="2"></td>
        </tr>

        <tr class="total-row">
            <td class="label">TOTAL:</td>
            <td class="value">{{ number_format($total, 0, ',', ' ') }}</td>
        </tr>
    </table>

    <div class="receipt-section">
        <p><strong>Reçu de M./Mme <span class="tenant-name">{{ strtoupper($locataire['nom']) }}</span></strong></p>
        <p>La somme de <span class="amount">{{ number_format($montant_paye, 0, ',', ' ') }} FCFA ({{ $montant_lettre }})</span></p>
        <p><strong>Nature de la transaction : {{ strtoupper($type_paiement ?? 'LOYER ' . strtoupper($mois_concerne)) }}</strong></p>
        <p>des locaux qu'il occupe chez : <span class="owner-name">{{ strtoupper($proprietaire['nom']) }}</span></p>
        @if($appartement)
            <p><strong>Appartement N°{{ $appartement['numero'] }} - Étage {{ $appartement['etage'] }}</strong></p>
        @endif
        <p><strong>Adresse :</strong> {{ $bien['adresse'] }}, {{ $bien['ville'] }}</p>
    </div>

    <div class="notes-section">
        <p class="location-date">Fait à Dakar, le {{ $date_paiement }}</p>

        <p class="nota-title">NOTA: Aucun locataire ne peut déménager sans:</p>
        <ol>
            <li>Qu'il n'ait donné ou reçu congé par écrit dans les délais</li>
            <li>Qu'il n'ait acquitté de ses consommations d'Eau, d'Électricité</li>
            <li>Qu'il n'ait acquitté de ses Impôts et taxes</li>
            <li>Il ne peut céder ni sous louer sans l'accord écrit du propriétaire</li>
            <li>Chaque jour de retard de paiement occasionne 5% de pénalité sur le loyer</li>
        </ol>
    </div>
</div>

</body>
</html>
