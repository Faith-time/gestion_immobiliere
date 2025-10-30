<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bail Commercial</title>
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
            border-left: 4px solid #ffc107;
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
            color: #ffc107;
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
        .note-importante {
            background: #fff3cd;
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #ffc107;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="header">
    <h1>BAIL COMMERCIAL (3-6-9 ANS)</h1>
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
    <h3>Le Locataire (Locataire Commercial) :</h3>
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
        Le présent bail commercial porte sur la location d'un local à usage commercial, industriel ou artisanal. Le preneur déclare vouloir exploiter dans les lieux un fonds de commerce et s'engage à exercer son activité conformément à la réglementation en vigueur.
    </div>
</div>

<div class="article">
    <div class="article-title">Article 2 – Désignation des locaux</div>
    <div class="article-content">
        <div class="bien-details">
            <strong>{{ $bien->title }}</strong><br>
            Superficie : {{ number_format($bien->superficy, 0, ',', ' ') }} m²<br>
            Nombre de pièces : {{ $bien->rooms }}<br>
            @if($bien->floors) Nombre d'étages : {{ $bien->floors }}<br> @endif
            Adresse : {{ $bien->address }}, {{ $bien->city }}<br>
            @if($bien->description) Description : {{ $bien->description }} @endif
        </div>
        <div class="note-importante">
            Note : Les locaux sont loués pour l'exploitation d'une activité commerciale, industrielle ou artisanale uniquement.
        </div>
    </div>
</div>

<div class="article">
    <div class="article-title">Article 3 – Destination des locaux</div>
    <div class="article-content">
        Les locaux sont exclusivement destinés à l'exercice d'une activité commerciale, industrielle ou artisanale. Le preneur s'interdit formellement d'affecter les lieux à usage d'habitation. Toute modification de la destination des locaux devra faire l'objet d'un accord écrit préalable du bailleur et respecter les réglementations d'urbanisme en vigueur.
    </div>
</div>

<div class="article">
    <div class="article-title">Article 4 – Durée du bail (Bail 3-6-9 ans)</div>
    <div class="article-content">
        Le bail est conclu pour une durée ferme de <strong>9 ans</strong>, du {{ \Carbon\Carbon::parse($location->date_debut)->format('d/m/Y') }}
        au {{ \Carbon\Carbon::parse($location->date_fin)->format('d/m/Y') }}.
        <br><br>
        <strong>Faculté de résiliation triennale :</strong>
        <ul style="margin-left: 20px; margin-top: 8px;">
            <li>Le preneur dispose d'une faculté de résiliation à l'expiration de chaque période triennale (à 3, 6 ou 9 ans)</li>
            <li>Cette faculté doit être exercée par acte d'huissier ou lettre recommandée avec AR</li>
            <li>Le préavis est de 6 mois avant l'échéance triennale</li>
            <li>Le bailleur ne peut résilier qu'à l'expiration des 9 ans, sauf motif grave et légitime</li>
        </ul>
        <br>
        À l'expiration du bail, celui-ci sera renouvelé par tacite reconduction pour une période de 9 ans, sauf congé donné dans les formes et délais légaux.
    </div>
</div>

<div class="article">
    <div class="article-title">Article 5 – Loyer et révision</div>
    <div class="article-content">
        Le loyer annuel hors taxes et hors charges est fixé à <span class="prix">{{ number_format($location->loyer_mensuel * 12, 0, ',', ' ') }} FCFA</span>,
        soit <span class="prix">{{ number_format($location->loyer_mensuel, 0, ',', ' ') }} FCFA</span> par mois.
        <br><br>
        Le loyer est payable trimestriellement à terme échu, par virement bancaire ou chèque, au domicile du bailleur ou en tout autre lieu qui pourrait être indiqué ultérieurement.
        <br><br>
        <strong>Révision du loyer :</strong> Le loyer sera révisé annuellement à la date anniversaire du contrat, en fonction de la variation de l'indice trimestriel des loyers commerciaux (ILC) ou de l'indice trimestriel des loyers des activités tertiaires (ILAT) publié par l'Institut National de la Statistique.
    </div>
</div>

<div class="article">
    <div class="article-title">Article 6 – Dépôt de garantie</div>
    <div class="article-content">
        Un dépôt de garantie de <span class="prix">{{ number_format($location->loyer_mensuel * 3, 0, ',', ' ') }} FCFA</span> (équivalent à 3 mois de loyer) a été versé par le preneur lors de la signature du bail. Ce dépôt sera restitué dans un délai de 3 mois après la fin du bail, déduction faite des sommes éventuellement dues et des dégradations constatées.
    </div>
</div>

<div class="article">
    <div class="article-title">Article 7 – Charges et taxes</div>
    <div class="article-content">
        Le preneur supportera en sus du loyer :
        <ul style="margin-left: 20px; margin-top: 8px;">
            <li>Les charges locatives (eau, électricité, chauffage, entretien des parties communes)</li>
            <li>La taxe d'enlèvement des ordures ménagères</li>
            <li>La taxe professionnelle et tous impôts commerciaux liés à son activité</li>
            <li>Les primes d'assurance pour ses risques locatifs et son activité</li>
            <li>L'entretien courant et les menues réparations des locaux</li>
        </ul>
    </div>
</div>

<div class="article">
    <div class="article-title">Article 8 – Travaux et aménagements</div>
    <div class="article-content">
        <strong>Par le preneur :</strong> Le preneur peut réaliser, à ses frais, les aménagements nécessaires à l'exercice de son activité, sous réserve de l'autorisation écrite du bailleur pour les travaux affectant la structure ou l'aspect extérieur du bâtiment.
        <br><br>
        <strong>Par le bailleur :</strong> Le bailleur s'engage à effectuer les grosses réparations et les travaux de mise en conformité avec les normes de sécurité et d'accessibilité en vigueur.
    </div>
</div>

<div class="article">
    <div class="article-title">Article 9 – Obligations du preneur</div>
    <div class="article-content">
        Le preneur s'engage à :
        <ul style="margin-left: 20px; margin-top: 8px;">
            <li>Payer le loyer et les charges aux échéances convenues</li>
            <li>Exploiter personnellement et effectivement le fonds de commerce</li>
            <li>Entretenir les locaux en bon état et y effectuer les réparations locatives</li>
            <li>Souscrire une assurance multirisque professionnelle couvrant les risques locatifs</li>
            <li>Respecter les réglementations relatives à son activité professionnelle</li>
            <li>Ne pas exercer d'activités illicites ou dangereuses</li>
            <li>Ne pas causer de troubles de jouissance au voisinage</li>
            <li>Obtenir toutes les autorisations administratives nécessaires à son activité</li>
        </ul>
    </div>
</div>

<div class="article">
    <div class="article-title">Article 10 – Obligations du bailleur</div>
    <div class="article-content">
        Le bailleur s'engage à :
        <ul style="margin-left: 20px; margin-top: 8px;">
            <li>Délivrer les locaux en bon état et conformes aux normes de sécurité</li>
            <li>Garantir la jouissance paisible des lieux</li>
            <li>Effectuer les grosses réparations (toiture, murs porteurs, fondations)</li>
            <li>Assurer la mise en conformité du local avec les normes en vigueur</li>
            <li>Ne pas s'opposer aux aménagements nécessaires à l'activité du preneur</li>
        </ul>
    </div>
</div>

<div class="article">
    <div class="article-title">Article 11 – Cession et sous-location</div>
    <div class="article-content">
        Le preneur peut céder son bail à l'acquéreur de son fonds de commerce ou de son entreprise. Toute sous-location, même partielle, est interdite sans l'accord écrit préalable du bailleur, qui ne peut le refuser sans motif légitime et sérieux.
    </div>
</div>

<div class="article">
    <div class="article-title">Article 12 – Droit au renouvellement</div>
    <div class="article-content">
        Le présent bail confère au preneur un droit au renouvellement conformément aux dispositions légales régissant les baux commerciaux. Le bailleur qui refuse le renouvellement devra verser au preneur une indemnité d'éviction, sauf en cas de motif grave et légitime.
    </div>
</div>

<div class="article">
    <div class="article-title">Article 13 – Résiliation</div>
    <div class="article-content">
        <strong>Résiliation triennale :</strong> Le preneur peut résilier le bail à l'expiration de chaque période de 3 ans moyennant un préavis de 6 mois.
        <br><br>
        <strong>Résiliation anticipée :</strong> En dehors des périodes triennales, la résiliation anticipée n'est possible qu'en cas de faute grave de l'une des parties ou par accord mutuel.
    </div>
</div>

<div class="article">
    <div class="article-title">Article 14 – Clause résolutoire</div>
    <div class="article-content">
        À défaut de paiement du loyer ou des charges aux termes convenus, ou en cas de non-respect des obligations du preneur, et un mois après un commandement de payer demeuré infructueux, le présent bail pourra être résilié de plein droit par le bailleur.
    </div>
</div>

<div class="date-lieu">
    Fait à {{ $ville_signature }}, le {{ $date_creation }}, en deux exemplaires originaux.
</div>

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
                        <img src="{{ $locataire_signature['data'] }}" alt="Signature preneur" class="signature-image" />
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
    <p>Cauris Immo - Keur Massar, Jaxaay, Unité 14, Dakar</p>
    <p>Téléphone: +221 77 448 32 28 - Email: caurisimmobiliere@gmail.com</p>
    @if(isset($signature_status) && $signature_status === 'entierement_signe')
        <p><strong>Document certifié avec signatures électroniques horodatées</strong></p>
    @endif
</div>
</body>
</html>
