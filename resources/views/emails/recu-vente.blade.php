<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu de Vente</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 650px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 26px;
        }
        .header p {
            margin: 10px 0 0 0;
            font-size: 16px;
        }
        .badge {
            display: inline-block;
            padding: 6px 15px;
            background: #28a745;
            color: white;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-top: 10px;
        }
        .content {
            padding: 30px 20px;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
        }
        .congratulations {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 25px;
            text-align: center;
            border-radius: 8px;
            margin: 25px 0;
        }
        .congratulations h2 {
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box h3 {
            margin: 0 0 15px 0;
            color: #667eea;
            font-size: 16px;
        }
        .info-box p {
            margin: 8px 0;
        }
        .info-box strong {
            color: #667eea;
        }
        .montant-box {
            background: #d4edda;
            border: 3px solid #28a745;
            padding: 25px;
            text-align: center;
            margin: 30px 0;
            border-radius: 10px;
        }
        .montant-box .label {
            font-size: 16px;
            color: #155724;
            margin-bottom: 10px;
            font-weight: bold;
        }
        .montant-box .montant {
            font-size: 36px;
            font-weight: bold;
            color: #155724;
            margin: 10px 0;
        }
        .montant-box .lettres {
            font-size: 13px;
            color: #155724;
            font-style: italic;
        }
        .bien-card {
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
            border: 2px solid #667eea;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        .bien-card h3 {
            margin: 0 0 15px 0;
            color: #667eea;
            font-size: 18px;
        }
        .repartition-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .repartition-table td {
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        .repartition-table td:last-child {
            text-align: right;
            font-weight: bold;
        }
        .repartition-table tr.total td {
            border-top: 2px solid #667eea;
            border-bottom: 2px solid #667eea;
            font-size: 18px;
            color: #667eea;
            padding: 15px 10px;
        }
        .important-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        .important-box h3 {
            margin: 0 0 15px 0;
            color: #856404;
        }
        .important-box ul {
            margin: 0;
            padding-left: 20px;
        }
        .important-box li {
            margin: 10px 0;
        }
        .next-steps {
            background: #e7f3ff;
            border: 2px solid #0056b3;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        .next-steps h3 {
            margin: 0 0 15px 0;
            color: #0056b3;
        }
        .next-steps ol {
            margin: 0;
            padding-left: 20px;
        }
        .next-steps li {
            margin: 10px 0;
        }
        .divider {
            height: 1px;
            background: #dee2e6;
            margin: 30px 0;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #dee2e6;
        }
        .footer p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
<div class="email-container">
    <div class="header">
        <h1>🏡 Félicitations !</h1>
        <p>Votre achat immobilier est confirmé</p>
        <div class="badge">✓ PAIEMENT VALIDÉ</div>
    </div>

    <div class="content">
        <div class="greeting">
            Bonjour <strong>{{ $acheteur['nom'] }}</strong>,
        </div>

        <div class="congratulations">
            <h2>🎉 Toutes nos félicitations !</h2>
            <p>Vous êtes désormais propriétaire de votre bien immobilier.</p>
        </div>

        <p>Nous avons le plaisir de vous confirmer la finalisation de votre acquisition immobilière auprès de <strong>Cauris Immobilier</strong>.</p>

        <!-- MONTANT TOTAL -->
        <div class="montant-box">
            <div class="label">💰 PRIX D'ACQUISITION</div>
            <div class="montant">{{ number_format($transaction['prix_total'], 0, ',', ' ') }} FCFA</div>
            <div class="lettres">{{ $montant_lettre }}</div>
        </div>

        <!-- INFORMATIONS BIEN -->
        <div class="bien-card">
            <h3>🏠 Votre Nouveau Bien</h3>
            <p><strong>{{ $bien['titre'] }}</strong></p>
            <p><strong>Type :</strong> {{ $bien['type'] }}</p>
            <p><strong>Localisation :</strong> {{ $bien['adresse'] }}, {{ $bien['ville'] }}</p>
            <p><strong>Superficie :</strong> {{ $bien['superficie'] }}</p>
            @if($bien['description'])
                <p style="margin-top: 10px; font-size: 14px; color: #666;">{{ $bien['description'] }}</p>
            @endif
        </div>

        <!-- RÉPARTITION PAIEMENT -->
        <div class="info-box">
            <h3>💳 Répartition du Paiement</h3>
            <table class="repartition-table">
                <tr>
                    <td>Dépôt de garantie (10%)</td>
                    <td>{{ number_format($transaction['depot_garantie'], 0, ',', ' ') }} FCFA</td>
                </tr>
                <tr>
                    <td>Solde payé (90%)</td>
                    <td>{{ number_format($transaction['montant_restant_paye'], 0, ',', ' ') }} FCFA</td>
                </tr>
                <tr class="total">
                    <td>MONTANT TOTAL</td>
                    <td>{{ number_format($transaction['prix_total'], 0, ',', ' ') }} FCFA</td>
                </tr>
            </table>
        </div>

        <!-- DÉTAILS TRANSACTION -->
        <div class="info-box">
            <h3>📋 Détails de la Transaction</h3>
            <p><strong>N° Reçu :</strong> {{ $numero_recu }}</p>
            <p><strong>Date de vente :</strong> {{ $date_vente }}</p>
            <p><strong>Date de paiement :</strong> {{ $paiement['date_paiement'] }}</p>
            <p><strong>Mode de paiement :</strong> {{ $paiement['mode_paiement'] }}</p>
            <p><strong>Référence :</strong> {{ $paiement['transaction_id'] }}</p>
        </div>

        <!-- INFORMATIONS VENDEUR -->
        <div class="info-box">
            <h3>👤 Vendeur</h3>
            <p><strong>Nom :</strong> {{ $vendeur['nom'] }}</p>
            <p><strong>Téléphone :</strong> {{ $vendeur['telephone'] }}</p>
        </div>

        <div class="divider"></div>

        <!-- PROCHAINES ÉTAPES -->
        <div class="next-steps">
            <h3>📌 Prochaines Étapes</h3>
            <ol>
                <li><strong>Signature de l'acte authentique :</strong> Un rendez-vous sera fixé chez le notaire dans les prochains jours</li>
                <li><strong>Transfert de propriété :</strong> Les démarches administratives seront finalisées par nos soins</li>
                <li><strong>Remise des clés :</strong> Vous serez contacté pour organiser la remise des clés</li>
                <li><strong>Documents officiels :</strong> Vous recevrez tous les documents nécessaires (titre de propriété, etc.)</li>
            </ol>
        </div>

        <!-- INFORMATIONS IMPORTANTES -->
        <div class="important-box">
            <h3>⚠️ Informations Importantes</h3>
            <ul>
                <li>Ce reçu atteste du paiement intégral du prix de vente convenu</li>
                <li>Conservez précieusement ce document comme preuve d'achat</li>
                <li>Un conseiller vous contactera sous 48h pour les démarches administratives</li>
                <li>La signature de l'acte authentique chez le notaire finalisera le transfert de propriété</li>
                <li>Les frais de notaire et d'enregistrement sont à la charge de l'acquéreur</li>
            </ul>
        </div>

        <!-- COMMISSION -->
        <div style="background: #e9ecef; padding: 15px; border-radius: 4px; margin: 20px 0;">
            <p style="margin: 0; font-size: 13px; color: #666;">
                <strong>Note :</strong> Une commission de <strong>{{ number_format($transaction['commission_agence'], 0, ',', ' ') }} FCFA</strong> (5% du prix de vente)
                a été retenue pour les services de l'agence. Le montant net de <strong>{{ number_format($transaction['montant_net_vendeur'], 0, ',', ' ') }} FCFA</strong>
                sera reversé au vendeur.
            </p>
        </div>

        <div class="divider"></div>

        <p>Votre reçu de vente détaillé est joint à cet email au format PDF.</p>

        <p style="margin-top: 30px;">
            Nous vous remercions de votre confiance et restons à votre disposition pour toute question.
        </p>

        <p style="margin-top: 20px;">
            Cordialement,<br>
            <strong>L'équipe Cauris Immobilier</strong>
        </p>
    </div>

    <div class="footer">
        <p><strong>CAURIS IMMOBILIER</strong></p>
        <p>Keur Massar Rond Point Jaxaay P.A.U 14</p>
        <p>Tél : 77 448 32 28 / 77 516 72 28 / 76 785 98 48</p>
        <p>Email : jacobleyla@hotmail.fr</p>
        <p style="margin-top: 15px; font-size: 11px;">
            Cet email a été envoyé automatiquement, merci de ne pas y répondre.
        </p>
    </div>
</div>
</body>
</html>
