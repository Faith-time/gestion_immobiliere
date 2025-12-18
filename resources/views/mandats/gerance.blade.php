<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MANDAT DE GÉRANCE - CAURIS IMMOBILIER</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            line-height: 1.5;
            color: #000;
            font-size: 11pt;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 18pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 10px 0;
            letter-spacing: 1px;
        }
        .agence-info {
            text-align: center;
            font-size: 10pt;
            margin-bottom: 20px;
            line-height: 1.4;
        }
        .agence-info strong {
            font-size: 12pt;
        }
        .partie {
            margin: 25px 0;
            line-height: 1.8;
        }
        .partie-title {
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .info-line {
            margin: 5px 0;
        }
        .article {
            margin: 20px 0;
            page-break-inside: avoid;
        }
        .article-title {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 10px;
        }
        .article-content {
            text-align: justify;
            margin-left: 0;
            line-height: 1.7;
        }
        .bien-details {
            background: #f5f5f5;
            padding: 15px;
            margin: 15px 0;
            border: 1px solid #ddd;
        }
        .prix-important {
            font-weight: bold;
            font-size: 12pt;
            color: #000;
        }
        .convenu {
            text-align: center;
            font-weight: bold;
            margin: 30px 0;
            font-size: 11pt;
        }
        .note-importante {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 10px;
            margin: 10px 0;
            font-weight: bold;
        }
        .signatures {
            margin-top: 50px;
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
            padding: 20px 10px;
        }
        .signature-box {
            border: 1px solid #000;
            min-height: 100px;
            position: relative;
            background: #fff;
            margin-top: 10px;
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
            font-size: 9pt;
            color: #333;
            margin-top: 5px;
        }
        .date-lieu {
            text-align: left;
            margin: 30px 0;
            font-style: italic;
        }
        .signature-status {
            margin: 15px 0;
            padding: 10px;
            border-radius: 3px;
            text-align: center;
            font-weight: bold;
            font-size: 10pt;
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
        ul {
            margin: 10px 0;
            padding-left: 25px;
        }
        li {
            margin: 5px 0;
        }
    </style>
</head>
<body>

<div class="header">
    <div class="agence-info">
        <strong>CAURIS IMMOBILIER</strong><br>
        ADRESSE : Keur Massar Rond Point Jaxaay P.A.U 14<br>
        RC : SN.DKR.2009.A.11649 | NINEA : 009017189<br>
        TEL : 77 448 32 28 / 77 516 72 28 / 76 785 98 48<br>
        EMAIL : jacobleyla@hotmail.fr
    </div>

    <h1>MANDAT DE GÉRANCE</h1>

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

<div class="partie-title">ENTRE</div>

<div class="partie">
    <div><strong>L'Agence immobilière CAURIS IMMO</strong> sis à Keur Massar/ Jaxaay-Parcelles Unité 14, d'une part</div>
</div>

<div class="partie-title">Et d'autre part</div>

<div class="partie">
    <div class="info-line"><strong>Nom et Prénom :</strong> {{ $proprietaire->name}} </div>
    <div class="info-line"><strong>Adresse :</strong> {{ $proprietaire->adresse ?? $bien->address }}</div>
    <div class="info-line"><strong>Email :</strong> {{ $proprietaire->email }}</div>
</div>

<div class="convenu">
    Il est convenu et arrêté ce qui suit
</div>

<div class="article">
    <div class="article-title">Article 1 : DÉSIGNATIONS</div>
    <div class="article-content">
        M/Mme/Mlle <strong>{{ $proprietaire->nom }} {{ $proprietaire->prenom }}</strong> en qualité de propriétaire confie par la présente à l'agence Cauris Immobilier qui accepte, la gérance dans le temps et aux conditions ci-dessous et cela conformément aux articles 457 et suivants du code des obligations civiles et commerciales applicables en la matière.

        <div class="bien-details">
            <strong>{{ $bien->title }}</strong><br>
            <strong>Localisation :</strong> {{ $bien->address }}, {{ $bien->city }}<br>
            <strong>Superficie :</strong> {{ number_format($bien->superficy, 0, ',', ' ') }} m²<br>
            @if($bien->rooms)<strong>Nombre de pièces :</strong> {{ $bien->rooms }}<br>@endif
            @if($bien->bathrooms)<strong>Salles de bains :</strong> {{ $bien->bathrooms }}<br>@endif
            @if($bien->kitchens)<strong>Cuisines :</strong> {{ $bien->kitchens }}<br>@endif
            @if($bien->living_rooms)<strong>Salons :</strong> {{ $bien->living_rooms }}<br>@endif
            @if($bien->floors)<strong>Nombre d'étages :</strong> R+{{ $bien->floors }}<br>@endif
            @if($bien->description)<strong>Description :</strong> {{ $bien->description }}@endif
        </div>
    </div>
</div>

<div class="article">
    <div class="article-title">Article 2 : Pouvoir du Mandataire</div>
    <div class="article-content">
        Le mandataire a les pouvoirs d'administration et de gestion habituellement dévolus au gérant dans le cadre de l'exécution des présentes.

        Il pourra ainsi dans cette limite prendre tout acte matériel ou juridique nécessaire allant dans le sens d'une bonne gestion de l'immeuble, notamment rechercher et passer des contrats avec des locataires et encaisser les loyers.

        <div class="note-importante">
            NB : Le propriétaire n'a pas le droit de récupérer le loyer au niveau des locataires.
        </div>
    </div>
</div>

<div class="article">
    <div class="article-title">Article 3 : Durée</div>
    <div class="article-content">
        Le présent mandat de gérance est conclu pour une durée de <strong>deux (02) ans</strong> renouvelable sous tacite reconduction et commençant à compter de sa date de signature. Les parties pouvant décider de sa prorogation dans le même terme.

        <div style="margin-top: 10px;">
            <strong>Début du mandat :</strong> {{ \Carbon\Carbon::parse($mandat->date_debut)->format('d/m/Y') }}<br>
            <strong>Fin du mandat :</strong> {{ \Carbon\Carbon::parse($mandat->date_fin)->format('d/m/Y') }}
        </div>

        <div style="margin-top: 10px;">
            En cas de non renouvellement ou de rupture du contrat, le propriétaire doit laisser au mandataire un délai de six (06) mois au moins pour reloger ses locataires dans d'autres locaux.
        </div>
    </div>
</div>

<div class="article">
    <div class="article-title">Article 4 : Loyer</div>
    <div class="article-content">
        Le montant du loyer total dû est fixé à : <span class="prix-important">{{ number_format($bien->price, 0, ',', ' ') }} FCFA</span> par mois.
    </div>
</div>

<div class="article">
    <div class="article-title">Article 5 : Assurance Justice</div>
    <div class="article-content">
        À la fin de chaque mois, le propriétaire versera à l'agence un montant de <strong>2 500 FCFA</strong> à titre d'assurance justice qui servira à couvrir les frais de justice en cas d'impayés. Ainsi les frais de justice seront supportés par l'agence en cas de procédure judiciaire.

        Cependant pour bénéficier de l'assurance justice, le propriétaire doit cotiser au moins six (6) mois. Tout propriétaire ne désirant pas adhérer à l'assurance se verra être obligé de prendre en charge lui-même les frais de justice en cas de procédure judiciaire.
    </div>
</div>

<div class="article">
    <div class="article-title">Article 6 : Paiement du Mandataire Gérant</div>
    <div class="article-content">
        À partir du 15 de chaque mois, le propriétaire viendra percevoir les recettes de la maison. S'il ne peut pas se déplacer, il peut :
        <ul>
            <li>Donner une autorisation expresse (procuration) à quelqu'un d'autre qui viendra retirer l'argent muni de sa pièce d'identité</li>
            <li>Donner son numéro de compte bancaire à l'agence, et cette dernière versera la somme sur son compte bancaire à partir de la date indiquée ci-dessus</li>
        </ul>

        @php
            // 🔥 CORRECTION : Forcer le taux à 10% pour la gérance
            $loyerMensuel = (float) $bien->price;
            $tauxCommission = 10.00; // ← TOUJOURS 10% pour la gérance

            // Calcul de la commission
            $commissionMensuelle = round(($loyerMensuel * $tauxCommission) / 100, 2);
            $netProprietaire = $loyerMensuel - $commissionMensuelle;
        @endphp

        <div style="margin-top: 10px;">
            <strong>La commission est de {{ number_format($tauxCommission, 2, ',', ' ') }}% de la location</strong>,
            soit <span class="prix-important">{{ number_format($commissionMensuelle, 0, ',', ' ') }} FCFA</span> par mois.
        </div>

        <div style="margin-top: 8px; font-size: 0.9em; color: #555;">
            <em>
                Le propriétaire percevra <strong>{{ number_format($netProprietaire, 0, ',', ' ') }} FCFA</strong>
                ({{ number_format(100 - $tauxCommission, 2, ',', ' ') }}% du loyer mensuel de {{ number_format($loyerMensuel, 0, ',', ' ') }} FCFA).
            </em>
        </div>
    </div>
</div>

<div class="article">
    <div class="article-title">Article 7 : Déclaration Fiscale</div>
    <div class="article-content">
        À la fin de chaque année, le bailleur doit lui-même déclarer ses revenus locatifs au service des impôts et domaines. Néanmoins, s'il estime qu'il n'est pas disponible pour le faire, il peut demander à l'Agence de s'en charger à sa place.

        L'agence facturera ce service pour un montant de <strong>vingt-cinq mille (25 000) FCFA</strong> annuel.
    </div>
</div>

<div class="article">
    <div class="article-title">Article 8 : Assurance du Bien</div>
    <div class="article-content">
        L'agence dégage toute responsabilité en cas d'incendie ou toute autre catastrophe. C'est pourquoi elle conseille fortement au propriétaire d'assurer sa maison au niveau des services d'assurances accrédités au pays.
    </div>
</div>

<div class="article">
    <div class="article-title">Article 9 : Clause Résolutoire</div>
    <div class="article-content">
        Le contrat peut être rompu à tout moment par l'une des deux parties avec bien entendu un préavis de trois (3) mois. Chaque partie s'engage à respecter l'ensemble des clauses du mandat de gérance. La résiliation pourrait être constatée par ordonnance de référé rendu par le juge des référés du tribunal à qui les parties donnent exclusivement compétence.
    </div>
</div>

@if($mandat->conditions_particulieres)
    <div class="article">
        <div class="article-title">Article 10 : Conditions Particulières</div>
        <div class="article-content">
            {{ $mandat->conditions_particulieres }}
        </div>
    </div>
@endif

<div class="date-lieu">
    Fait à Keur Massar, le {{ $date_creation }}
</div>

<div class="signatures">
    <table>
        <tr>
            <td>
                <div><strong>Le Mandataire :</strong></div>
                <div class="signature-box">
                    @if(isset($agence_signature) && $agence_signature['is_signed'] && $agence_signature['data'])
                        <img src="{{ $agence_signature['data'] }}" alt="Signature agence" class="signature-image" />
                        <div class="signature-info">
                            Cauris Immobilier<br>
                            <small>Signé le {{ \Carbon\Carbon::parse($agence_signature['signed_at'])->format('d/m/Y à H:i') }}</small>
                        </div>
                    @else
                        <div class="signature-placeholder">
                            Signature et cachet<br>
                            Cauris Immobilier
                        </div>
                    @endif
                </div>
            </td>
            <td>
                <div><strong>Le Mandant :</strong></div>
                <div class="signature-box">
                    @if(isset($proprietaire_signature) && $proprietaire_signature['is_signed'] && $proprietaire_signature['data'])
                        <img src="{{ $proprietaire_signature['data'] }}" alt="Signature propriétaire" class="signature-image" />
                        <div class="signature-info">
                            {{ $proprietaire->nom }} {{ $proprietaire->prenom }}<br>
                            <small>Signé le {{ \Carbon\Carbon::parse($proprietaire_signature['signed_at'])->format('d/m/Y à H:i') }}</small>
                        </div>
                    @else
                        <div class="signature-placeholder">
                            Signature<br>
                            {{ $proprietaire->nom }} {{ $proprietaire->prenom }}
                        </div>
                    @endif
                </div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
