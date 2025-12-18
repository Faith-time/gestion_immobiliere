<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrat de Location à Usage d'Habitation</title>
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
    CONTRAT DE LOCATION A USAGE D'HABITATION
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
    <div class="article-title">Article 1 : LES DÉSIGNATIONS</div>
    <div class="article-content">
        Le bailleur {{ $bailleur->name }} donne en location à {{ $locataire->name }} les lieux à usage exclusivement
        d'habitation, qui les accepte comme tels :<br>
        <strong>{{ $bien->title }}</strong><br>
        Situé à : {{ $bien->address }}, {{ $bien->city }}<br>
        Superficie : {{ number_format($bien->superficy, 0, ',', ' ') }} m²<br>
        Composition : {{ $bien->rooms }} chambre(s), {{ $bien->living_rooms }} salon(s),
        {{ $bien->kitchens }} cuisine(s), {{ $bien->bathrooms }} salle(s) de bain<br><br>
        Un état des lieux sera fait contradictoirement avec le preneur lors de la prise de possession des lieux.
    </div>
</div>

<div class="section">
    <div class="article-title">I. LOYER</div>
    <div class="article-content">
        La présente location est acceptée et consentie moyennant un loyer mensuel de
        <strong>{{ number_format($location->loyer_mensuel, 0, ',', ' ') }} FCFA TTC</strong> par mois,
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
    en état des lieux.<br><br>
    La caution couvre les garanties suivantes :<br>
    - Eventuelles dégradations du bien immobilier<br>
    - Eventuels arriérés de loyer<br>
    - Production des comptes clôturés des abonnements SENELEC, SONATEL et SDE<br><br>
    A défaut il sera prélevé sur ladite caution les sommes correspondantes aux frais de remise en état des lieux,
    ainsi que le montant des factures d'eau, d'électricité et de téléphone non réglé. Il demeure bien entendu que
    ces formalités de remise en état et réalisation des travaux devront être effectuées pendant la période du préavis.
</div>

<div class="section">
    <div class="article-title">III. DURÉE</div>
    <div class="article-content">
        Le présent bail à usage d'habitation commence à compter du
        <strong>{{ \Carbon\Carbon::parse($location->date_debut)->format('d/m/Y') }}</strong>
        et est consenti pour une période de <strong>{{ \Carbon\Carbon::parse($location->date_debut)->diffInMonths($location->date_fin) }} mois</strong>,
        soit jusqu'au <strong>{{ \Carbon\Carbon::parse($location->date_fin)->format('d/m/Y') }}</strong>,
        renouvelable par tacite reconduction.
    </div>
</div>

<div class="section">
    <div class="article-title">IV. CHARGES ET CONDITIONS</div>
    <div class="article-content">
        Le présent bail est accepté aux charges et conditions ordinaires de droits ci-après :
    </div>
</div>

<div class="article">
    <div class="article-title">Article 1 : TENUE DES LOCAUX</div>
    <div class="article-content">
        1/ Le preneur reconnaît par la présente, prendre les lieux loués en bon état locatif et s'engage en conséquence
        à les rendre au moment de son départ, en parfait état d'entretien. Il s'engage formellement à acquitter exactement,
        pendant toute la durée de son occupation, le paiement des loyers toutes taxes comprises et l'exécution des
        conditions du bail.<br><br>
        2/ Le preneur veille à ne pas troubler la jouissance paisible des voisins par le bruit, la fumée ou autrement
        de fait ou du fait de ses ayants-droits ou de ses préposés.<br><br>
        3/ Le preneur s'engage à assurer ses biens, des risques locatifs, d'incendies et de vol d'objet de valeur ou
        autre et à faire recours aux services d'une compagnie d'assurance accréditée au Sénégal, faute de quoi toute
        responsabilité du bailleur est dégagée en cas d'avènement de tout événement malheureux. C'est pourquoi il est
        fortement conseillé au locataire de souscrire à une compagnie d'assurance habilitée afin d'être couvert au besoin.<br><br>
        4/ Le preneur ne pourra faire aucun aménagement, aucune transformation ou modification dans la disposition des
        locaux sans l'autorisation expresse préalable du bailleur. Tout embellissement, aménagement, amélioration des
        locaux appartiendront de plein droit au bailleur en fin de contrat.
    </div>
</div>

<div class="article">
    <div class="article-title">Article 2 : SOUS-LOCATION</div>
    <div class="article-content">
        Par ailleurs le preneur ne peut céder ou sous-louer en tout ou partie, sans l'autorisation formelle et par
        écrit de l'agence sous peine de résiliation du contrat.
    </div>
</div>

<div class="article">
    <div class="article-title">Article 3 : ANIMAUX</div>
    <div class="article-content">
        Pour maintenir une bonne hygiène de vie, l'élevage ou la détention d'animaux domestiques ou sauvages est
        formellement interdit.
    </div>
</div>

<div class="article">
    <div class="article-title">Article 4 : IMPÔTS ET TAXES</div>
    <div class="article-content">
        Le preneur satisfera à toutes les prescriptions des services de police de voierie et d'hygiène, afin que le
        bailleur ne soit nullement inquiété à ce sujet. Il versera exactement les contributions, taxes et patentes et
        tout autre impôt pouvant exister ou être établi, à raison d'occupation des lieux loués. Il s'acquittera également,
        dans les mois, de la taxe d'enlèvement sur les ordures ménagères.
    </div>
</div>

<div class="article">
    <div class="article-title">Article 5 : CHARGES LOCATIVES</div>
    <div class="article-content">
        Les consommations d'eau, d'électricité, et de téléphone ainsi que la vidange des fosses septiques sont à la
        charge exclusive du locataire.
    </div>
</div>

<div class="article">
    <div class="article-title">Article 6 : PRÉAVIS</div>
    <div class="article-content">
        La partie ayant pris l'initiative de la rupture du contrat doit en informer son vis-à-vis par acte extrajudiciaire
        contre récépissé ou par exploit d'huissier <strong>six mois à l'avance pour le bailleur</strong> et
        <strong>deux mois pour le preneur</strong>.<br><br>
        Lorsque le preneur aura reçu ou donné congé, le bailleur pourra faire mettre un écriteau à l'emplacement de son
        choix indiquant que les lieux sont à louer. Le preneur devra laisser visiter les jours ouvrables sur rendez-vous
        au moins 3 fois par semaine. Il en serait de même en cas de mise en vente.
    </div>
</div>

<div class="article">
    <div class="article-title">Article 7 : RÉVISION DU LOYER</div>
    <div class="article-content">
        Chaque année, le bailleur se réserve le droit de réviser le loyer pour se conformer au prix actuel du marché
        ou répondre aux exigences de l'inflation.
    </div>
</div>

<div class="article">
    <div class="article-title">Article 8 : TRAVAUX</div>
    <div class="article-content">
        Le propriétaire ou le bailleur peut effectuer des aménagements, des modifications ou de gros travaux (exemple:
        élévation) dans son immeuble sans obligation d'informer le ou les occupants. Il n'a pas besoin de son ou de leur
        approbation pour exécuter ces travaux. Toute entrave ou opposition du preneur constitue une clause résolutoire.
    </div>
</div>

<div class="section">
    <div class="article-title">V. CLAUSES RÉSOLUTOIRES</div>
    <div class="article-content">
        A défaut du paiement d'un seul terme de loyer à son échéance ou l'inexécution d'une quelconque des clauses et
        conditions du bail, celui-ci sera résilié de plein droit si bon semble au bailleur sans formalité judiciaire,
        <strong>trente (30) jours</strong> après une simple mise en demeure par lettre recommandée de payer ou d'exécuter,
        demeurée sans effet, quelle que soit la cause de cette carence et nonobstant toutes consignations ultérieures,
        l'expulsion sera prononcée par simple ordonnance de référé.<br><br>
        En cas d'occupation des lieux après la cessation du bail, il sera dû par l'occupant jusqu'à son expulsion, une
        indemnité égale au <strong>double du loyer</strong> et des charges contractuelles.<br><br>
        En cas de résiliation du bail aux torts du locataire, le dépôt de garantie restera acquis au bailleur à titre
        d'indemnité conventionnelle.<br><br>
        Tous les frais de justice (émolument d'huissier de justice, honoraires d'avocat, enrôlement etc…) supportés par
        l'agence lors d'une procédure judiciaire du fait du manquement du locataire à ses obligations seront totalement
        remboursés par celui-ci lors de la régulation de sa situation.
    </div>
</div>

<div class="section">
    <div class="article-title">VI. IMPÔTS ET TAXES</div>
    <div class="article-content">
        Les frais et honoraires des présentes ainsi que les droits d'enregistrement et de timbres sont à la charge
        exclusive du preneur ainsi que toutes les pénalités exigibles de son fait sans recours contre l'agence immobilière
        ou le propriétaire.<br><br>
        Enregistrement de <strong>02% du montant Toutes Taxes Comprises</strong> à payer aux impôts par le preneur pour une année.<br><br>
        Le preneur fera également le nécessaire pour que le renouvellement des droits d'enregistrement soit réglé en temps
        utile afin que le bailleur ne puisse être inquiété.
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
