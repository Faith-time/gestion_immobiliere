<?php

namespace App\Services;

class AIChatService
{
    private array $responses = [
        // === SALUTATIONS ET POLITESSE ===
        'bonjour|salut|hello|bonsoir|coucou|hey|hi' => [
            "Bonjour ! 👋 Je suis votre assistant immobilier virtuel. Comment puis-je vous aider aujourd'hui ?",
            "Salut et bienvenue ! Je suis là pour répondre à toutes vos questions sur l'immobilier. Que recherchez-vous ?",
            "Bonjour ! Ravi de vous accueillir. Je peux vous renseigner sur nos biens, les visites, les locations et bien plus encore.",
            "Hello ! Comment allez-vous ? Je suis votre conseiller virtuel. Que puis-je faire pour vous ?",
        ],

        'merci|remercie|sympa|gentil|cool' => [
            "Avec grand plaisir ! 😊 N'hésitez pas si vous avez d'autres questions.",
            "Je vous en prie ! C'est mon rôle de vous assister. Y a-t-il autre chose ?",
            "Ravi d'avoir pu vous aider ! Je reste disponible pour toute autre demande.",
            "De rien ! Je suis toujours là pour vous accompagner dans votre projet immobilier.",
        ],

        'au revoir|bye|adieu|à bientôt' => [
            "Au revoir ! N'hésitez pas à revenir si vous avez besoin d'aide. Bonne journée ! 👋",
            "À bientôt ! Je vous souhaite une excellente recherche immobilière.",
            "Bye ! Revenez quand vous voulez, je serai toujours là pour vous aider.",
        ],

        // === PRIX ET BUDGET ===
        'prix|tarif|coût|montant|combien|cher|budget' => [
            "Les prix varient selon plusieurs critères : type de bien (appartement, maison, villa), superficie, localisation, et prestations incluses. Pour vous donner une estimation précise, pourriez-vous me préciser quel type de bien vous intéresse et dans quelle zone ?",
            "Nos biens sont proposés à des tarifs compétitifs adaptés au marché local. Les prix des locations démarrent généralement à partir de 150 000 FCFA/mois et les biens à vendre à partir de 15 millions FCFA. Quel est votre budget approximatif ?",
            "Le coût dépend du type de transaction (location ou achat), de la superficie et du quartier. Je peux vous présenter des options dans différentes gammes de prix. Avez-vous une fourchette budgétaire en tête ?",
            "Pour vous proposer les meilleures options dans votre budget, j'aurais besoin de connaître : votre budget maximum, le type de bien souhaité, et la zone géographique privilégiée. Pouvez-vous me donner ces informations ?",
        ],

        // === LOCATION ===
        'louer|location|bail|locataire|loyer' => [
            "Excellente décision de louer ! 🏠 Nous avons de nombreux biens disponibles. Pour affiner ma recherche, dites-moi : combien de chambres souhaitez-vous ? Quel quartier vous intéresse ? Et quel est votre budget mensuel ?",
            "Nos locations incluent des studios, des appartements de 1 à 4 pièces, et des villas. Les baux sont généralement d'un an renouvelable. Quel type de logement recherchez-vous ?",
            "Pour une location, voici ce que je peux vous proposer : appartements meublés ou non meublés, maisons individuelles, et résidences sécurisées. Avez-vous des critères spécifiques comme un parking, un jardin, ou la proximité des transports ?",
            "Les locations sont disponibles dans tous les quartiers de la ville. Préférez-vous un bien en centre-ville, dans un quartier résidentiel calme, ou près des zones commerciales ?",
        ],

        'durée bail|combien temps location|durée location|période bail' => [
            "Les baux de location sont généralement conclus pour une durée minimale d'un an, renouvelable par tacite reconduction. Des baux de courte durée (3 à 6 mois) sont également possibles pour certains biens meublés. Quelle durée envisagez-vous ?",
            "La durée standard d'un bail est de 12 mois, mais nous pouvons négocier des durées plus longues (2 à 3 ans) avec conditions avantageuses. Avez-vous une préférence ?",
        ],

        'caution|dépôt garantie|avance' => [
            "La caution locative correspond généralement à 2 mois de loyer pour les biens non meublés et 3 mois pour les biens meublés. Cette somme est restituée en fin de bail, déduction faite des éventuels dégâts. Des questions sur les modalités de versement ?",
            "Le dépôt de garantie vous sera restitué dans un délai de 30 jours après l'état des lieux de sortie, si le logement est rendu en bon état. Nous effectuons un état des lieux détaillé à l'entrée pour éviter tout litige.",
        ],

        'meublé|meublée|non meublé|vide|équipé' => [
            "Nous proposons les deux options : biens meublés (avec cuisine équipée, meubles, électroménager) et non meublés (vides). Les meublés sont parfaits pour une installation rapide, tandis que les non meublés offrent plus de liberté d'aménagement. Quelle est votre préférence ?",
            "Les appartements meublés incluent généralement : lit, armoire, table, chaises, réfrigérateur, cuisinière, et parfois TV et lave-linge. Le loyer est légèrement supérieur mais vous économisez sur l'achat de meubles. Cela vous intéresse ?",
        ],

        // === ACHAT/VENTE ===
        'acheter|achat|acquérir|propriétaire|devenir propriétaire' => [
            "Devenir propriétaire est un excellent investissement ! 🏡 Nous avons un large catalogue de biens à vendre : appartements, maisons, villas, terrains. Pour commencer, quel type de bien recherchez-vous et dans quel budget ?",
            "L'achat d'un bien immobilier nécessite de définir plusieurs critères : budget, zone géographique, type de bien, et nombre de pièces. Je peux vous accompagner dans toutes les étapes. Avez-vous déjà une idée précise de ce que vous cherchez ?",
            "Nos biens à la vente vont de l'appartement F2 à 25 millions FCFA à la villa de luxe à plus de 200 millions FCFA. Nous proposons aussi des terrains constructibles. Quel est votre projet ?",
        ],

        'vendre|vente|mettre en vente|vendre bien|vendre maison' => [
            "Vous souhaitez vendre votre bien ? Parfait ! 📋 Nous pouvons vous accompagner avec une estimation gratuite, la mise en ligne de votre annonce, l'organisation des visites, et la gestion administrative complète. Parlez-moi de votre bien : type, superficie, localisation ?",
            "Pour vendre rapidement et au meilleur prix, nous vous proposons : une estimation professionnelle, des photos de qualité, une diffusion sur tous nos canaux, et un accompagnement juridique. Quand souhaitez-vous mettre votre bien sur le marché ?",
        ],

        'estimation|évaluation|valeur bien|combien vaut' => [
            "Nous réalisons des estimations gratuites et précises de votre bien en tenant compte du marché actuel, de la localisation, de l'état du bien, et des transactions récentes dans le secteur. Pour une estimation, j'ai besoin de quelques informations : adresse, type de bien, superficie, et nombre de pièces.",
            "L'évaluation de votre bien est gratuite et sans engagement. Nos experts analysent les prix du marché pour vous proposer une fourchette réaliste. Souhaitez-vous prendre rendez-vous pour une estimation ?",
        ],

        'hypothèque|crédit|financement|prêt|banque' => [
            "Nous travaillons avec plusieurs partenaires bancaires pour faciliter votre financement. Nos conseillers peuvent vous orienter vers les meilleures offres de crédit immobilier. Les taux actuels varient entre 6% et 9% selon les établissements. Souhaitez-vous être mis en relation avec un courtier ?",
            "Le financement est une étape cruciale. Nous pouvons vous aider à monter votre dossier de prêt immobilier et négocier les meilleures conditions avec nos partenaires bancaires. Avez-vous déjà contacté une banque ?",
        ],

        // === TYPES DE BIENS ===
        'appartement|flat|appart' => [
            "Nous avons de nombreux appartements disponibles : studios, F2, F3, F4, dans différents quartiers et gammes de prix. Certains sont dans des résidences sécurisées avec piscine et parking. Combien de pièces recherchez-vous ?",
            "Les appartements que nous proposons vont du studio de 25m² au F5 de 150m². Certains disposent de balcons, terrasses, ou d'accès à des espaces communs (salle de sport, piscine). Quelles sont vos priorités ?",
        ],

        'maison|villa|pavillon|résidence' => [
            "Nos maisons et villas offrent plus d'espace et d'intimité : jardin privé, plusieurs chambres, garage, parfois piscine. Elles sont idéales pour les familles. Cherchez-vous une maison en centre-ville ou en périphérie ?",
            "Les villas disponibles vont de la maison de 3 chambres à la villa de standing avec 5+ chambres, piscine, et grand jardin. Les prix varient de 50 millions à 300 millions FCFA selon la localisation et les prestations.",
        ],

        'studio|t1|f1|une pièce' => [
            "Les studios sont parfaits pour une personne seule ou un jeune couple : compact, économique, et facile à entretenir. Nos studios font entre 20 et 35m² avec coin cuisine et salle de bain. Budget mensuel pour une location ?",
            "Nous avons plusieurs studios disponibles dans différents quartiers, avec des loyers allant de 80 000 à 180 000 FCFA/mois selon la localisation et les équipements. Certains sont meublés. Quelle est votre préférence ?",
        ],

        'terrain|parcelle|lot|foncier' => [
            "Nous proposons des terrains constructibles dans plusieurs zones : lotissements viabilisés, terrains en bord de mer, parcelles agricoles. Les superficies vont de 200m² à plusieurs hectares. Quel type de projet avez-vous (construction résidentielle, commerciale, agricole) ?",
            "Les terrains sont vendus avec tous les documents en règle : titre foncier, autorisation de construire possible, accès aux réseaux (eau, électricité). Les prix varient de 15 000 à 50 000 FCFA/m² selon la zone. Quelle superficie recherchez-vous ?",
        ],

        'commercial|bureau|local commercial|boutique' => [
            "Nos locaux commerciaux sont situés dans des zones stratégiques à fort passage : centres commerciaux, artères principales, quartiers d'affaires. Superficie de 30m² à 500m² disponibles. Quel type d'activité envisagez-vous ?",
            "Pour les bureaux et espaces professionnels, nous proposons : open spaces, bureaux cloisonnés, plateaux modulables, avec parking et sécurité. Les loyers vont de 300 000 à 2 000 000 FCFA/mois. Combien de postes de travail prévoyez-vous ?",
        ],

        // === VISITES ===
        'visite|visiter|voir|rendez-vous visite' => [
            "Les visites sont essentielles pour vous faire une idée précise du bien ! 🔑 Nous organisons des visites du lundi au samedi, entre 9h et 18h. Pour quel bien souhaitez-vous organiser une visite ? Je peux vérifier les disponibilités.",
            "Je peux vous programmer une visite guidée avec un de nos agents. Nous vous présenterons le bien, le quartier, et répondrons à toutes vos questions sur place. Quand seriez-vous disponible ? (matin, après-midi, ou samedi)",
            "Les visites peuvent être individuelles ou groupées. Nous pouvons aussi organiser des visites virtuelles en vidéo si vous ne pouvez pas vous déplacer. Quelle formule préférez-vous ?",
        ],

        'quand visiter|horaire visite|disponibilité visite' => [
            "Nos agents sont disponibles pour les visites du lundi au vendredi de 9h à 18h, et le samedi de 9h à 14h. Nous pouvons aussi organiser des visites en soirée sur rendez-vous. Quel créneau vous conviendrait le mieux ?",
            "Je peux vous proposer plusieurs créneaux cette semaine. Les visites durent généralement 30 à 45 minutes. Préférez-vous en matinée (9h-12h) ou l'après-midi (14h-18h) ?",
        ],

        'visite virtuelle|visite en ligne|visite 3d|vidéo' => [
            "Nous proposons des visites virtuelles en 3D pour certains biens, ainsi que des vidéos et des appels vidéo en direct avec un agent. C'est pratique pour faire un premier tri avant de vous déplacer. Souhaitez-vous une visite virtuelle ?",
            "Les visites virtuelles 360° vous permettent d'explorer le bien depuis chez vous, à tout moment. Nous pouvons aussi organiser un FaceTime ou WhatsApp vidéo avec un agent sur place. Quelle option préférez-vous ?",
        ],

        // === QUARTIERS ET LOCALISATION ===
        'quartier|zone|secteur|localisation|où|emplacement' => [
            "La localisation est cruciale ! 📍 Nous couvrons tous les quartiers de la ville : centre-ville animé, zones résidentielles calmes, quartiers en développement, bord de mer... Avez-vous des préférences en termes de proximité (transports, écoles, commerces, plages) ?",
            "Chaque quartier a ses atouts : certains sont proches des écoles et commerces, d'autres offrent plus de calme et d'espaces verts, certains ont une vie nocturne active. Quels sont vos critères prioritaires pour le choix du quartier ?",
            "Je peux vous proposer des biens dans différents secteurs selon vos besoins : quartiers populaires et dynamiques, zones huppées et sécurisées, nouveaux lotissements en développement. Quel type d'environnement recherchez-vous ?",
        ],

        'centre ville|centre-ville|downtown|hypercentre' => [
            "Le centre-ville offre tous les avantages de la proximité : commerces, restaurants, bureaux, transports en commun à pied. Les biens y sont prisés mais plus chers. Nos appartements en centre-ville vont de 200 000 à 500 000 FCFA/mois en location. Cela correspond à votre budget ?",
            "Les biens en hypercentre sont parfaits pour ceux qui travaillent en ville et veulent éviter les trajets. Vous serez à 10 minutes à pied de tout : banques, administrations, loisirs. Cherchez-vous un appartement ou un local commercial ?",
        ],

        'banlieue|périphérie|extérieur ville|campagne' => [
            "Les zones périphériques offrent plus d'espace pour moins cher : maisons avec jardin, calme, air pur. Idéal pour les familles. Les prix sont 30 à 50% moins élevés qu'en centre-ville. Avez-vous une voiture pour les déplacements ?",
            "En banlieue, vous trouverez de belles villas modernes dans des lotissements sécurisés, avec des commodités (écoles, supermarchés) à proximité. Le trajet vers le centre-ville est de 20-30 minutes. Cela vous conviendrait ?",
        ],

        'mer|plage|bord de mer|front de mer|océan' => [
            "Les biens en bord de mer sont très recherchés ! 🌊 Nous avons des villas et appartements avec vue sur l'océan, accès direct à la plage, et ambiance vacances toute l'année. Les prix sont premium mais l'emplacement est exceptionnel. Budget approximatif ?",
            "Vivre près de la mer c'est profiter de la brise marine, des activités nautiques, et d'un cadre de vie idyllique. Nos biens en front de mer vont de l'appartement de standing à la villa de luxe. Cherchez-vous une résidence principale ou secondaire ?",
        ],

        'transport|bus|métro|taxi|circulation|accès' => [
            "L'accès aux transports est un critère important. Je peux vous proposer des biens à proximité des arrêts de bus, des stations de taxi, ou des axes routiers principaux. Dépendez-vous des transports en commun ou avez-vous un véhicule ?",
            "Certains quartiers sont très bien desservis avec des bus toutes les 10 minutes, tandis que d'autres nécessitent un véhicule personnel. Je peux filtrer les biens selon vos contraintes de mobilité. Quels sont vos besoins en transport ?",
        ],

        'école|collège|lycée|université|éducation' => [
            "La proximité des établissements scolaires est essentielle pour les familles ! 🎒 Je peux vous proposer des biens situés près d'écoles primaires, de collèges, ou d'universités. Avez-vous des enfants ? Quelle tranche d'âge ?",
            "Plusieurs de nos biens sont dans des quartiers réputés pour leurs bonnes écoles, à moins de 10 minutes à pied. Nous pouvons aussi vous renseigner sur la qualité des établissements du secteur. Combien d'enfants avez-vous ?",
        ],

        'commerce|magasin|supermarché|marché|courses' => [
            "La proximité des commerces facilite le quotidien ! 🛒 Je peux vous proposer des biens situés près de supermarchés, marchés locaux, pharmacies, et autres commodités. Préférez-vous tout à pied ou acceptez-vous 5-10 minutes en voiture ?",
            "Les quartiers avec commerces de proximité sont très prisés. Vous aurez tout à portée de main : boulangerie, épicerie, restaurant. Certains biens sont même dans des résidences avec commerces intégrés. Cela vous intéresse ?",
        ],

        // === DOCUMENTS ET ADMINISTRATIF ===
        'document|dossier|papier|pièce justificative|administratif' => [
            "Pour constituer votre dossier, voici les documents généralement requis : pièce d'identité (CNI ou passeport), justificatifs de revenus (3 dernières fiches de paie ou attestation d'employeur), justificatif de domicile actuel, et parfois une attestation bancaire. Avez-vous besoin d'aide pour préparer votre dossier ?",
            "Les documents nécessaires varient selon votre projet (location ou achat). Pour une location : CNI, revenus, domicile. Pour un achat : en plus il faut des relevés bancaires et éventuellement une attestation de prêt. De quel type de transaction s'agit-il ?",
            "Nous vous accompagnons dans la constitution de votre dossier et vérifions que tout est en ordre avant de le soumettre au propriétaire ou notaire. Cela accélère le processus. Souhaitez-vous connaître la liste complète des documents ?",
        ],

        'cni|carte identité|passeport|identité' => [
            "La carte nationale d'identité (CNI) ou le passeport en cours de validité est obligatoire pour toute transaction immobilière. C'est la pièce principale pour vérifier votre identité. Avez-vous un document d'identité valide ?",
            "Assurez-vous que votre CNI ou passeport est à jour (non expiré) avant de constituer votre dossier. Si votre pièce est périmée, il faudra la renouveler. Votre document est-il valide ?",
        ],

        'revenu|salaire|fiche de paie|bulletin salaire|revenus' => [
            "Les justificatifs de revenus permettent d'évaluer votre capacité financière. Il faut généralement les 3 dernières fiches de paie OU une attestation d'employeur OU vos relevés bancaires des 3 derniers mois. Êtes-vous salarié, indépendant, ou entrepreneur ?",
            "Pour les salariés : fiches de paie récentes. Pour les indépendants : attestation fiscale ou relevés bancaires professionnels. Pour les entrepreneurs : bilan comptable. Dans quelle situation êtes-vous ?",
        ],

        'garant|caution solidaire|personne qui se porte garant' => [
            "Un garant (ou caution solidaire) peut être demandé si vos revenus sont insuffisants ou irréguliers. Cette personne s'engage à payer le loyer si vous ne pouvez pas. Le garant doit fournir les mêmes documents que vous (revenus, identité). Avez-vous quelqu'un qui peut se porter garant ?",
            "Le garant doit généralement gagner au moins 3 fois le montant du loyer. Il peut être un parent, un ami, ou un organisme de cautionnement. Avez-vous besoin d'aide pour trouver une caution ?",
        ],

        // === CONTRATS ET SIGNATURES ===
        'contrat|bail|acte|signature|signer' => [
            "Les contrats sont gérés de manière sécurisée via notre plateforme numérique avec signature électronique certifiée. Le contrat de location (bail) ou l'acte de vente précise tous les détails : prix, conditions, durée, responsabilités. Avez-vous des questions sur la procédure de signature ?",
            "La signature peut se faire en ligne (signature électronique) ou en agence avec notre présence. Le contrat est ensuite enregistré légalement. Préférez-vous signer électroniquement ou en personne ?",
            "Nos contrats sont conformes à la réglementation en vigueur et protègent les deux parties. Je peux vous expliquer chaque clause avant signature. Souhaitez-vous un accompagnement juridique ?",
        ],

        'mandat|mandat de vente|mandat de location|exclusivité' => [
            "Le mandat nous autorise à commercialiser votre bien. Il peut être simple (vous gardez la liberté de vendre par vous-même) ou exclusif (nous sommes votre seul intermédiaire, mais on s'investit plus). Le mandat dure généralement 3 à 6 mois. Quelle formule préférez-vous ?",
            "Avec un mandat exclusif, nous nous engageons à mettre tous nos moyens pour vendre/louer rapidement : photos pro, pub, visites organisées, négociations. En contrepartie, nous demandons l'exclusivité pendant la durée du mandat. Êtes-vous d'accord avec ce principe ?",
        ],

        'notaire|acte notarié|enregistrement|officiel' => [
            "Pour les achats/ventes, le passage chez le notaire est obligatoire pour officialiser la transaction et obtenir le titre de propriété. Les frais de notaire représentent environ 10% du prix de vente. Nous travaillons avec plusieurs notaires de confiance. Souhaitez-vous une recommandation ?",
            "L'acte notarié authentifie la vente et vous protège juridiquement. Le notaire vérifie tous les documents, calcule les taxes, et enregistre la transaction. Cela prend généralement 1 à 2 mois. Avez-vous déjà choisi un notaire ?",
        ],

        // === PAIEMENTS ===
        'paiement|payer|règlement|mode paiement|comment payer' => [
            "Nous acceptons plusieurs modes de paiement sécurisés : virement bancaire, mobile money (Wave, Orange Money), chèque certifié, et paiement en ligne par carte bancaire via notre plateforme. Pour quelle prestation souhaitez-vous effectuer un paiement ?",
            "Les paiements peuvent être effectués en une fois ou en plusieurs échéances selon l'accord. Pour les locations, le loyer est généralement payable mensuellement d'avance. Pour les achats, un acompte de 10-30% est demandé à la réservation. Des questions sur les modalités ?",
            "Notre système de paiement en ligne est sécurisé avec cryptage SSL et conforme aux normes bancaires internationales. Vous recevrez un reçu électronique après chaque transaction. Quel montant souhaitez-vous régler ?",
        ],

        'virement|transfert|bancaire|banque' => [
            "Pour un virement bancaire, voici nos coordonnées : [Nom de la banque] - IBAN : XX... - Code BIC : XXX. Merci d'indiquer votre nom et la référence du bien en objet du virement. Confirmez-vous que vous souhaitez payer par virement ?",
            "Le virement bancaire est le mode de paiement le plus sécurisé pour les gros montants. Il prend 24 à 48h pour être crédité sur notre compte. Nous vous confirmerons la réception par email. Souhaitez-vous les coordonnées bancaires ?",
        ],

        'mobile money|wave|orange money|espèces|cash' => [
            "Nous acceptons les paiements par mobile money : Wave, Orange Money, Free Money. Pratique et instantané ! Pour les montants élevés, nous recommandons le virement bancaire pour plus de sécurité et de traçabilité. Quel montant souhaitez-vous payer ?",
            "Le paiement en espèces est possible jusqu'à un certain montant (généralement 500 000 FCFA). Au-delà, nous recommandons le virement ou le mobile money pour votre sécurité. Quel est le montant à régler ?",
        ],

        'échéance|délai paiement|mensualité|facilités paiement' => [
            "Nous proposons des facilités de paiement pour certains biens : paiement en 2, 3 ou même 12 mensualités sans frais supplémentaires. Cela nécessite un dossier validé et un premier versement de 30%. Seriez-vous intéressé par un paiement échelonné ?",
            "Les échéances de loyer sont généralement mensuelles, payables les 5 premiers jours du mois. Pour les achats, nous pouvons négocier un échéancier avec le vendeur selon votre situation. Quel est votre besoin ?",
        ],

        // === RÉSERVATIONS ===
        'réserver|réservation|bloquer|retenir bien' => [
            "Pour réserver un bien et le bloquer le temps de constituer votre dossier, nous demandons un acompte de réservation : 50 000 à 100 000 FCFA pour une location, 5 à 10% du prix pour un achat. Cet acompte est déduit du montant total ou remboursé si la transaction n'aboutit pas pour une raison indépendante de votre volonté. Souhaitez-vous réserver ?",
            "La réservation garantit que le bien ne sera pas proposé à d'autres clients pendant 7 à 15 jours. Cela vous laisse le temps de visiter, réfléchir et constituer votre dossier. Avez-vous déjà identifié le bien qui vous intéresse ?",
        ],

        'annuler|annulation réservation|rembours|restitution' => [
            "L'acompte de réservation est remboursable dans certains cas : si votre dossier est refusé par le propriétaire, si le bien présente des vices cachés non mentionnés, ou si le vendeur se rétracte. En revanche, si vous annulez sans motif valable, l'acompte peut être retenu. Avez-vous un motif valable d'annulation ?",
            "Pour annuler une réservation, merci de nous contacter dans les meilleurs délais. Selon les circonstances et le contrat signé, nous étudierons les modalités de remboursement. Quelle est la raison de votre annulation ?",
        ],

        // === ÉTAT DU BIEN ET ÉQUIPEMENTS ===
        'état|condition|rénové|neuf|ancien|travaux' => [
            "L'état des biens varie : certains sont neufs ou récemment rénovés, d'autres sont anciens mais bien entretenus, et quelques-uns nécessitent des travaux. Le prix est ajusté en conséquence. Préférez-vous un bien clé en main ou acceptez-vous de faire des travaux pour négocier le prix ?",
            "Nous indiquons toujours l'état du bien dans l'annonce : neuf, excellent état, bon état, à rafraîchir, ou à rénover. Les photos et la visite vous permettront de juger. Quel état de bien recherchez-vous ?",
        ],

        'parking|garage|stationnement' => [
            "Beaucoup de nos biens incluent un parking ou garage privé, sécurisé. C'est un atout majeur en ville ! Le parking peut être couvert, en sous-sol, ou extérieur. Est-ce un critère indispensable pour vous ?",
            "Si le bien n'a pas de parking privé, nous vous indiquerons les possibilités de stationnement à proximité : parking public, rue avec stationnement libre. Avez-vous une ou plusieurs voitures ?",
        ],

        'jardin|terrasse|balcon|extérieur' => [
            "Les espaces extérieurs sont très appréciés ! 🌳 Nous avons des biens avec jardins privés (de 50m² à plusieurs centaines de m²), terrasses aménagées, ou balcons. Parfait pour les barbecues, les enfants, ou simplement se détendre. Quelle taille d'espace extérieur souhaitez-vous ?",
            "Un balcon/terrasse permet de profiter de l'air frais sans avoir à entretenir un jardin. Les jardins offrent plus d'espace mais demandent plus d'entretien. Quelle est votre préférence ?",
        ],

        'piscine|jacuzzi|spa' => [
            "Les biens avec piscine sont disponibles principalement dans les villas et résidences haut de gamme. 🏊 Certaines résidences ont une piscine commune, d'autres ont des piscines privées. Le budget est plus élevé mais le confort incomparable ! Cela vous intéresse ?",
            "Une piscine privée nécessite un entretien régulier (produits, nettoyage). Certains propriétaires incluent l'entretien dans les charges, d'autres le laissent à votre charge. Êtes-vous prêt à gérer l'entretien ?",
        ],

        'climatisation|clim|chauffage' => [
            "La plupart de nos biens sont équipés de climatisation, indispensable sous nos climats ! ❄️ Certains ont la clim dans toutes les pièces, d'autres seulement dans les chambres et le salon. Les frais d'électricité augmentent avec l'usage. Est-ce un équipement obligatoire pour vous ?",
            "Nous proposons des biens avec climatisation centrale (silencieuse, performante) ou des splits individuels (flexibles, économiques). Quelle est votre préférence ?",
        ],

        'sécurité|gardien|gardiennage|alarme|surveillance' => [
            "La sécurité est primordiale ! 🔒 Beaucoup de nos résidences ont un gardien 24h/24, des caméras de surveillance, un portail électrique, et parfois des rondes de sécurité. Les quartiers résidentiels privés sont particulièrement sécurisés. Quel niveau de sécurité recherchez-vous ?",
            "Les systèmes de sécurité incluent : gardiennage, vidéosurveillance, alarmes, digicodes, badges d'accès. Certains biens ont tout, d'autres sont plus simples. Avez-vous des exigences particulières en matière de sécurité ?",
        ],

        'internet|wifi|fibre|connexion' => [
            "La connexion internet est essentielle aujourd'hui ! 📡 Nous vérifions toujours l'éligibilité à la fibre optique ou à l'ADSL haut débit. La plupart de nos biens en ville sont raccordables à la fibre. Travaillez-vous depuis chez vous ? Avez-vous besoin d'une connexion très performante ?",
            "Certaines résidences proposent une connexion internet incluse dans les charges. Sinon, vous devrez souscrire un abonnement auprès d'un FAI local (Orange, Sonatel, etc.). Souhaitez-vous un bien avec internet inclus ?",
        ],

        'eau|électricité|charges|fluides' => [
            "Les charges incluent généralement l'eau, l'électricité, et parfois l'entretien des parties communes. Le montant varie selon votre consommation. En moyenne : 20 000 à 40 000 FCFA/mois pour un appartement, plus pour une maison. Les compteurs individuels permettent de payer selon votre usage. Avez-vous des questions sur les charges ?",
            "L'eau et l'électricité peuvent être inclus dans le loyer (forfait) ou facturés selon consommation réelle (compteur individuel). La seconde option est plus juste mais peut varier d'un mois à l'autre. Quelle formule préférez-vous ?",
        ],

        // === SUPERFICIE ET DIMENSIONS ===
        'superficie|surface|taille|dimension|m2|mètre carré' => [
            "La superficie est un critère clé ! Nos biens vont de 25m² (studio) à plus de 500m² (villa de luxe). Pour vous aider : un studio = 20-35m², F2 = 40-60m², F3 = 60-80m², F4 = 80-120m², maison = 100-300m². Quelle superficie minimale recherchez-vous ?",
            "Pour déterminer la taille dont vous avez besoin, comptez environ : 25-30m² par personne pour un confort optimal. Une famille de 4 personnes sera à l'aise dans 100m² minimum. Combien êtes-vous dans le foyer ?",
        ],

        'chambre|pièce|nombre chambres|combien chambres' => [
            "Le nombre de chambres dépend de votre famille : 1 chambre pour un couple, 2-3 chambres pour une petite famille, 4+ chambres pour une grande famille. Nous avons des biens de 1 à 6 chambres. Combien de chambres vous faut-il ?",
            "Les chambres sont généralement spacieuses (10-15m² chacune) avec placards intégrés. Certains biens ont une suite parentale avec salle de bain privative. Avez-vous besoin d'une configuration particulière ?",
        ],

        'salle de bain|douche|toilette|wc|sanitaire' => [
            "Les biens ont généralement 1 à 3 salles de bain selon la taille. Les configurations incluent : salle de bain complète (baignoire, douche, lavabo, WC), salle d'eau (douche, lavabo), WC séparés. Quelle configuration préférez-vous ?",
            "Une salle de bain pour 2 chambres est le standard. Les grandes maisons ont souvent une salle de bain principale et une salle d'eau secondaire. Les WC peuvent être intégrés ou séparés. Avez-vous une préférence ?",
        ],

        // === DURÉE ET DISPONIBILITÉ ===
        'disponible|disponibilité|quand|libre|immédiat' => [
            "Certains biens sont disponibles immédiatement, d'autres dans 1 à 3 mois (fin de bail en cours, travaux de rénovation). Nous mettons à jour quotidiennement les disponibilités. À partir de quand souhaitez-vous emménager ?",
            "Si vous avez un besoin urgent (moins d'un mois), je peux filtrer uniquement les biens libres immédiatement. Si vous êtes flexible, vous aurez plus de choix. Quelle est votre deadline ?",
        ],

        'combien temps|délai|procédure|processus' => [
            "La procédure complète prend généralement : 2-5 jours pour valider le dossier, 7-15 jours pour signer le bail/contrat, et vous pouvez emménager immédiatement après signature et paiement. Pour un achat, comptez 1-3 mois avec le notaire. Des questions sur le timing ?",
            "Nous faisons notre maximum pour accélérer le processus ! Avec un dossier complet et un paiement rapide, vous pouvez emménager en moins de 2 semaines. Avez-vous votre dossier prêt ?",
        ],

        // === NÉGOCIATION ===
        'négocier|négociation|réduire prix|rabais|remise' => [
            "La négociation est possible sur certains biens, surtout s'ils sont sur le marché depuis longtemps ou si vous payez cash rapidement. 💰 La marge de négociation varie de 5% à 15% selon le bien et le vendeur. Souhaitez-vous que je teste une négociation pour un bien particulier ?",
            "Pour maximiser vos chances de négociation : montrez votre sérieux (dossier complet, capacité de paiement), soyez réactif, et proposez une offre raisonnable (pas -30% !). Je peux vous accompagner dans la négociation. Quel bien vous intéresse ?",
        ],

        // === PROBLÈMES ET RÉCLAMATIONS ===
        'problème|panne|réparation|maintenance' => [
            "En cas de problème dans le bien loué, vous devez immédiatement contacter le propriétaire ou notre service après-vente. Les réparations urgentes (fuite, panne électrique) doivent être traitées sous 48h. Les autres peuvent prendre une semaine. Quel est le problème rencontré ?",
            "Pour les petites réparations (ampoule, robinet), c'est généralement à votre charge. Pour les gros problèmes (toiture, chaudière), c'est au propriétaire. Le bail précise les responsabilités de chacun. De quel type de réparation s'agit-il ?",
        ],

        'litige|conflit|désaccord|réclamation' => [
            "En cas de litige avec le propriétaire ou l'agence, nous recommandons d'abord une médiation à l'amiable. Si cela échoue, vous pouvez saisir le tribunal compétent. Nous conservons tous les documents pour faciliter la résolution. Quel est le sujet du litige ?",
            "Nous faisons notre maximum pour éviter les conflits en étant transparents et en documentant tout (état des lieux, photos, contrats clairs). Si un désaccord survient, parlons-en calmement. Quelle est votre préoccupation ?",
        ],

        // === INVESTISSEMENT ===
        'investissement|investir|rentabilité|rendement' => [
            "L'immobilier est un excellent investissement ! 📈 La rentabilité locative moyenne est de 6-10% brut par an. Les zones en développement offrent plus de potentiel de plus-value. Je peux vous conseiller sur les meilleurs secteurs pour investir. Quel est votre budget d'investissement ?",
            "Pour un investissement rentable, privilégiez : bonne localisation, forte demande locative, prix d'achat raisonnable, charges limitées. Les studios et F2 en ville se louent très bien. Cherchez-vous un investissement à court terme (revente rapide) ou long terme (rente locative) ?",
        ],

        'défiscalisation|impôt|taxe|fiscal' => [
            "Certains investissements immobiliers permettent de réduire vos impôts grâce à des dispositifs fiscaux. Je vous recommande de consulter un conseiller fiscal pour optimiser votre investissement. Souhaitez-vous que je vous mette en relation avec un expert ?",
            "Les taxes immobilières incluent : taxe foncière (payée par le propriétaire), taxe d'habitation (parfois payée par le locataire), et droits d'enregistrement. Le montant varie selon le bien et la commune. Avez-vous des questions fiscales ?",
        ],

        // === ASSURANCE ===
        'assurance|assurer|garantie' => [
            "L'assurance habitation est obligatoire pour les locataires et fortement recommandée pour les propriétaires. Elle couvre : incendie, dégâts des eaux, vol, responsabilité civile. Les tarifs vont de 30 000 à 100 000 FCFA/an selon le bien. Êtes-vous déjà assuré ?",
            "Nous travaillons avec plusieurs compagnies d'assurance qui proposent des tarifs préférentiels à nos clients. L'assurance peut être incluse dans le loyer ou souscrite séparément. Souhaitez-vous une recommandation d'assureur ?",
        ],

        // === COLOCATION ===
        'colocation|colocataire|partager|roommate' => [
            "La colocation est une solution économique et conviviale ! 🏠👥 Nous proposons des appartements adaptés (plusieurs chambres, espaces communs spacieux). Chaque colocataire signe le bail et paie sa part. Cherchez-vous une coloc existante ou voulez-vous créer la vôtre ?",
            "Pour une colocation réussie : choisissez bien vos colocataires (compatible sur le mode de vie), définissez les règles (ménage, bruit, invités), et répartissez clairement les charges. Combien de colocataires envisagez-vous ?",
        ],

        // === ANIMAUX ===
        'animal|chien|chat|animaux compagnie' => [
            "Les animaux de compagnie sont acceptés dans certains biens, sous conditions (taille, nombre, caution supplémentaire possible). 🐕🐈 Nous filtrons pour vous les biens où les animaux sont autorisés. Avez-vous un animal ? Quelle espèce et quelle taille ?",
            "Si vous avez un animal, mentionnez-le dès le début pour éviter les surprises. Certains propriétaires demandent une clause spécifique au bail ou une caution majorée. Êtes-vous prêt à ces conditions ?",
        ],

        // === ACCESSIBILITÉ ===
        'handicap|accessibilité|pmr|mobilité réduite' => [
            "Nous avons des biens adaptés aux personnes à mobilité réduite : plain-pied ou avec ascenseur, portes élargies, salle de bain aménagée. ♿ L'accessibilité est un critère important. Avez-vous des besoins spécifiques en termes d'accessibilité ?",
            "Les normes d'accessibilité imposent : rampe d'accès, ascenseur, portes de 90cm minimum, WC adapté. Tous les biens neufs sont conformes. Pour l'ancien, nous vérifions au cas par cas. Quels sont vos besoins précis ?",
        ],

        // === CONTACT ET SUPPORT ===
        'contact|contacter|téléphone|email|joindre' => [
            "Vous pouvez nous contacter de plusieurs façons : par téléphone au [XXX XXX XXXX], par email à contact@agence.com, via ce chat, ou en venant directement à l'agence. Nos horaires sont : lundi-vendredi 9h-18h, samedi 9h-13h. Comment préférez-vous être contacté ?",
            "Notre équipe est disponible et réactive ! Nous répondons généralement sous 2 heures en semaine. Pour une urgence, appelez directement. Sinon, ce chat ou un email fonctionnent très bien. Quel est le meilleur moyen de vous joindre ?",
        ],

        'adresse|où se trouve|localisation agence|située|où êtes|où est votre agence' => [
            "Notre agence est située au [Adresse complète de l'agence]. Nous sommes facilement accessibles en transport en commun et disposons d'un parking pour nos visiteurs. Souhaitez-vous passer nous voir ?",
            "Vous nous trouverez au [Numéro et nom de rue, Quartier, Ville]. Nous sommes ouverts du lundi au vendredi de 9h à 18h et le samedi de 9h à 13h. N'hésitez pas à venir sans rendez-vous ou à nous appeler avant votre visite !",
            "Notre agence se trouve à [Adresse], près de [Point de repère connu]. Vous pouvez nous joindre au [XXX XXX XXXX] pour plus d'informations sur l'itinéraire. À bientôt !",
        ],

        'disponible|disponibilité client|je suis disponible|je peux|semaine prochaine|lundi|mardi|mercredi|jeudi|vendredi|samedi|dimanche' => [
            "Parfait ! J'ai noté votre disponibilité. Je vais transmettre ces informations à notre équipe qui vous contactera pour fixer un rendez-vous au créneau qui vous convient le mieux. Préférez-vous être contacté par téléphone ou par email ?",
            "Merci pour ces précisions ! Nos agents organiseront la visite selon vos disponibilités. Généralement, nous confirmons les rendez-vous sous 24h. Y a-t-il un créneau horaire particulier qui vous arrange (matin, après-midi, soirée) ?",
            "Excellent ! Je note vos disponibilités. Pour finaliser la prise de rendez-vous, pourriez-vous me confirmer : s'agit-il d'une visite de bien, d'un rendez-vous conseil, ou d'une autre demande ?",
        ],

        'partir de|à partir|dès|commence|début|date emménagement' => [
            "Très bien, j'ai noté votre date souhaitée d'emménagement. Je vais rechercher les biens disponibles à cette période. En attendant, avez-vous d'autres critères importants pour votre recherche (nombre de chambres, quartier, budget) ?",
            "Parfait ! Avec cette date en tête, je peux vous proposer des biens qui seront libres à ce moment-là. Pour affiner ma recherche, pourriez-vous me préciser votre budget et le type de bien souhaité ?",
            "Entendu ! Je note cette échéance. Cela nous laisse le temps de trouver le bien idéal et de préparer votre dossier sereinement. Parlons maintenant de vos besoins : quel type de logement recherchez-vous ?",
        ],

        'rendez-vous|rdv|rencontrer|voir un agent' => [
            "Je peux vous programmer un rendez-vous avec un de nos conseillers pour discuter de votre projet en détail. Les rendez-vous peuvent avoir lieu à l'agence ou par visioconférence. Quand seriez-vous disponible ? (indiquez jour et horaire)",
            "Un rendez-vous personnalisé dure environ 30 à 60 minutes. Nous faisons le point sur vos besoins, votre budget, et vous présentons une sélection de biens adaptés. Souhaitez-vous prendre rendez-vous ?",
        ],

        'horaire|ouverture|heure ouverture|fermeture|quelles sont vos horaires|quelle heure ouvrez|quelle heure fermez' => [
            "Notre agence est ouverte du lundi au vendredi de 9h00 à 18h00 et le samedi de 9h00 à 13h00. Nous sommes fermés les dimanches et jours fériés. 🕐 Ce chat est disponible 24h/24 pour répondre à vos questions. Souhaitez-vous passer nous voir ?",
            "Nos horaires d'ouverture : Lundi-Vendredi 9h-18h, Samedi 9h-13h, Fermé le dimanche. En dehors de ces horaires, vous pouvez utiliser ce chat ou nous laisser un message, nous vous recontacterons rapidement. Avez-vous une question urgente ?",
            "Nous sommes ouverts en semaine de 9h à 18h et le samedi matin de 9h à 13h. Notre équipe est disponible pendant ces créneaux pour vous recevoir en agence ou par téléphone. Ce chat virtuel reste accessible 24h/24. Souhaitez-vous prendre rendez-vous ?",
        ],

        // === QUESTIONS SPÉCIFIQUES ===
        'expatrié|étranger|visa|permis séjour' => [
            "Les expatriés et étrangers sont les bienvenus ! 🌍 Pour louer, vous aurez besoin d'un passeport valide, d'un visa ou permis de séjour en cours de validité, et de justificatifs de revenus (contrat de travail local ou international). Des garanties supplémentaires peuvent être demandées. Êtes-vous déjà sur place ?",
            "Nous accompagnons régulièrement des expatriés dans leur installation. Nous pouvons vous aider à trouver un logement avant votre arrivée (visites virtuelles) et gérer les démarches administratives. De quel pays venez-vous ?",
        ],

        'étudiant|campus|université|logement étudiant' => [
            "Les logements étudiants sont très demandés ! 🎓 Nous proposons des studios et F2 à proximité des campus, avec des loyers adaptés (100 000 à 200 000 FCFA/mois). Un garant (parent) est généralement requis. Dans quelle université étudiez-vous ?",
            "Pour les étudiants, nous recommandons : studio ou colocation, proche des transports, budget raisonnable. Les résidences étudiantes offrent sécurité et convivialité. Quel est votre budget maximum ?",
        ],

        'famille|enfant|école proche' => [
            "Les biens adaptés aux familles ont : plusieurs chambres, espaces extérieurs (jardin, terrasse), proximité des écoles et parcs, quartier calme et sécurisé. 👨‍👩‍👧‍👦 Nous avons de nombreuses options familiales. Combien d'enfants avez-vous et quels âges ?",
            "Pour les familles, je recommande les maisons ou grands appartements (F4+) dans les quartiers résidentiels. Les écoles, crèches, et espaces de jeux sont à proximité. Quel type de bien préférez-vous ?",
        ],

        'retraité|senior|calme|tranquille' => [
            "Pour les retraités, nous recommandons des biens calmes, accessibles (plain-pied ou avec ascenseur), bien desservis (commerces, santé à proximité), et dans des quartiers sécurisés et paisibles. 🌿 Recherchez-vous plutôt centre-ville ou périphérie ?",
            "Les résidences seniors offrent services adaptés, sécurité renforcée, et vie communautaire. Pour plus d'indépendance, un appartement classique en rez-de-chaussée peut convenir. Quelle formule préférez-vous ?",
        ],

        // === SAISONNALITÉ ===
        'vacances|saisonnier|court terme|courte durée' => [
            "Nous proposons aussi des locations saisonnières pour les vacances : villas en bord de mer, appartements en ville, disponibles à la semaine ou au mois. Les tarifs sont plus élevés qu'en location longue durée mais avec plus de flexibilité. Combien de temps souhaitez-vous louer ?",
            "Les locations de courte durée (moins de 3 mois) sont meublées et équipées. Parfait pour les vacances ou les déplacements professionnels. Les prix varient selon la saison (haute ou basse). Pour quelles dates cherchez-vous ?",
        ],

        // === QUALITÉ DE VIE ===
        'qualité vie|cadre vie|environnement|ambiance' => [
            "La qualité de vie dépend de vos priorités : certains préfèrent l'animation du centre-ville, d'autres le calme de la périphérie. Nous vous aidons à trouver le quartier qui correspond à votre style de vie. Décrivez-moi votre journée idéale, cela m'aidera à vous conseiller.",
            "Un bon cadre de vie inclut : sécurité, propreté, verdure, commerces accessibles, bonne ambiance de voisinage. Nous connaissons bien les quartiers et pouvons vous orienter. Quels sont vos critères prioritaires ?",
        ],

        'voisinage|voisin|copropriété|communauté' => [
            "Le voisinage est important pour votre bien-être ! Nous vous informons sur l'ambiance générale du quartier (familial, jeune, mixte). Dans les copropriétés, il y a souvent une association de copropriétaires. Préférez-vous un quartier familial ou plus jeune et dynamique ?",
            "Les résidences avec espaces communs (piscine, salle de sport) favorisent les rencontres entre voisins. Les maisons individuelles offrent plus d'intimité. Quelle formule vous attire ?",
        ],

        // === AIDE ET SUPPORT ===
        'aide|aider|besoin|comprends pas|expliquer' => [
            "Je suis là pour vous aider à chaque étape ! 😊 Que ce soit pour trouver un bien, comprendre les démarches, préparer votre dossier, ou négocier le prix. N'hésitez pas à me poser toutes vos questions, même les plus simples. Que puis-je clarifier pour vous ?",
            "Pas de problème, je vais vous expliquer ! L'immobilier peut sembler complexe mais je suis là pour simplifier. Quelle partie vous semble floue ? (les prix, les documents, la procédure, les quartiers...) ?",
        ],

        'confus|compliqué|difficile|perdu' => [
            "Je comprends que cela puisse paraître compliqué. Reprenons étape par étape : 1) Définir vos besoins (type de bien, budget, localisation), 2) Sélectionner des biens, 3) Visiter, 4) Constituer le dossier, 5) Signer. À quelle étape êtes-vous ? Je peux détailler chaque point.",
            "Pas de panique ! Je vais vous guider simplement. Commençons par le début : cherchez-vous à louer ou à acheter ? Et pour quelle utilisation (habitation, investissement, professionnel) ?",
        ],

        // === URGENCE ===
        'urgent|rapidement|vite|pressé' => [
            "Je comprends votre urgence ! ⚡ Pour accélérer au maximum : 1) Préparez votre dossier complet dès maintenant, 2) Soyez flexible sur certains critères, 3) Acceptez de visiter rapidement, 4) Soyez prêt à signer vite. Dans combien de temps devez-vous emménager ?",
            "Nous avons des biens disponibles immédiatement. Avec un dossier complet et un paiement rapide, vous pouvez emménager en moins d'une semaine. Dites-moi vos critères essentiels et je vous trouve des options disponibles immédiatement.",
        ],
    ];

    private array $defaultResponses = [
        "C'est une excellente question ! Pour vous donner une réponse précise et personnalisée, pourriez-vous me fournir plus de détails sur votre situation et vos besoins ?",
        "Je note votre demande. Pour mieux vous conseiller, pourriez-vous préciser un peu plus votre question ? Par exemple, concernez-vous une location, un achat, ou une autre prestation ?",
        "Intéressant ! Je veux m'assurer de bien comprendre votre besoin. Pouvez-vous reformuler ou me donner plus de contexte sur ce que vous recherchez ?",
        "Merci pour votre question. Bien que je n'aie pas d'information spécifique sur ce point précis, je vous invite à contacter directement notre service client au [XXX XXX XXXX] ou à prendre rendez-vous avec un conseiller qui pourra vous répondre en détail.",
        "Hmm, je ne suis pas sûr d'avoir bien compris votre question. Pourriez-vous la reformuler différemment ? Ou peut-être me dire ce que vous cherchez à savoir exactement ?",
        "Je comprends votre préoccupation. Pour une réponse parfaitement adaptée à votre situation, je vous recommande de discuter avec un de nos conseillers spécialisés. Souhaitez-vous que je vous mette en relation ?",
        "C'est une question pertinente ! Cependant, pour vous donner la meilleure réponse possible, j'aurais besoin de quelques informations complémentaires. Pouvez-vous m'en dire plus ?",
    ];

    public function generateResponse(string $userMessage, array $conversationContext = []): string
    {
        $message = $this->normalizeMessage($userMessage);

        // Analyse contextuelle : détecter si c'est une réponse à une question précédente
        $isFollowUpAnswer = $this->isFollowUpAnswer($message, $conversationContext);

        if ($isFollowUpAnswer) {
            return $this->handleFollowUpAnswer($message, $conversationContext);
        }

        $scores = [];

        foreach ($this->responses as $pattern => $possibleResponses) {
            $keywords = explode('|', $pattern);
            $score = 0;
            $matchedKeywords = 0;

            foreach ($keywords as $keyword) {
                // Mot-clé exact trouvé
                if ($this->containsKeyword($message, $keyword)) {
                    $matchedKeywords++;
                    $score += 15; // Score de base augmenté

                    // Bonus si le mot-clé est au début (intention principale)
                    if ($this->startsWithKeyword($message, $keyword)) {
                        $score += 10; // Bonus augmenté
                    }

                    // Bonus pour les mots-clés très spécifiques
                    if (strlen($keyword) > 12) {
                        $score += 5;
                    }

                    // Bonus si le mot-clé apparaît plusieurs fois
                    $occurrences = substr_count($message, $keyword);
                    if ($occurrences > 1) {
                        $score += $occurrences * 3;
                    }
                }

                // Recherche partielle (score réduit)
                if ($this->containsPartialKeyword($message, $keyword) && !$this->containsKeyword($message, $keyword)) {
                    $score += 2; // Score réduit pour partiel
                }
            }

            // Bonus si plusieurs mots-clés de la même catégorie sont présents
            if ($matchedKeywords > 1) {
                $score += $matchedKeywords * 8;
            }

            if ($score > 0) {
                $scores[$pattern] = $score;
            }
        }

        // Seuil minimum pour éviter les faux positifs
        $threshold = 12;

        if (!empty($scores)) {
            arsort($scores);
            $bestScore = reset($scores);

            // Vérifier que le meilleur score dépasse le seuil
            if ($bestScore >= $threshold) {
                $bestPattern = array_key_first($scores);
                $responses = $this->responses[$bestPattern];
                return $responses[array_rand($responses)];
            }
        }

        // Si aucune correspondance forte, réponse par défaut
        return $this->defaultResponses[array_rand($this->defaultResponses)];
    }

    /**
     * Détecte si le message est une réponse à une question de l'IA
     */
    private function isFollowUpAnswer(string $message, array $context): bool
    {
        if (empty($context) || !isset($context['last_bot_message'])) {
            return false;
        }

        $lastBotMessage = strtolower($context['last_bot_message']);

        // Vérifier si le dernier message de l'IA contenait une question
        $hasQuestion = str_contains($lastBotMessage, '?');

        // Vérifier si le message utilisateur est court et ressemble à une réponse
        $messageWords = str_word_count($message);
        $isShortAnswer = $messageWords <= 20;

        // Mots indicateurs de réponse
        $answerIndicators = [
            'oui', 'non', 'ok', 'd\'accord', 'bien sûr', 'peut-être',
            'je suis', 'je peux', 'je veux', 'je cherche', 'je souhaite',
            'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche',
            'matin', 'après-midi', 'soir', 'semaine',
            'partir de', 'à partir', 'dès', 'commence'
        ];

        foreach ($answerIndicators as $indicator) {
            if (str_contains($message, $indicator)) {
                return $hasQuestion && $isShortAnswer;
            }
        }

        return false;
    }

    /**
     * Gère les réponses de suivi contextuel
     */
    private function handleFollowUpAnswer(string $message, array $context): string
    {
        $lastBotMessage = strtolower($context['last_bot_message'] ?? '');

        // Déterminer le type de question posée
        if (str_contains($lastBotMessage, 'disponible') || str_contains($lastBotMessage, 'quand')) {
            return "Parfait ! J'ai bien noté votre disponibilité. Je transmets ces informations à notre équipe qui vous contactera rapidement pour organiser le rendez-vous. Préférez-vous être contacté par téléphone ou par email ?";
        }

        if (str_contains($lastBotMessage, 'budget') || str_contains($lastBotMessage, 'prix')) {
            return "Très bien ! Avec ce budget, je peux vous proposer plusieurs options intéressantes. Avez-vous des préférences concernant le quartier ou le type de bien ?";
        }

        if (str_contains($lastBotMessage, 'chambre') || str_contains($lastBotMessage, 'pièce')) {
            return "Entendu ! Je recherche des biens correspondant à ce nombre de pièces. Dans quel quartier souhaitez-vous vous installer ?";
        }

        if (str_contains($lastBotMessage, 'quartier') || str_contains($lastBotMessage, 'zone')) {
            return "Excellent choix de zone ! Je vais vous sélectionner les meilleures options disponibles dans ce secteur. Souhaitez-vous que je vous envoie quelques annonces par email ?";
        }

        // Réponse générique pour les follow-up
        return "Merci pour ces précisions ! Ces informations vont nous permettre de mieux cibler votre recherche. Y a-t-il d'autres critères importants pour vous ?";
    }

    private function normalizeMessage(string $message): string
    {
        $message = strtolower($message);
        $message = preg_replace('/[!?]{2,}/', ' ', $message);
        $message = $this->removeAccents($message);
        $message = preg_replace('/\s+/', ' ', $message);
        return trim($message);
    }

    private function removeAccents(string $string): string
    {
        $unwanted = [
            'à' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i',
            'ô' => 'o', 'ö' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c',
        ];
        return strtr($string, $unwanted);
    }

    private function containsKeyword(string $message, string $keyword): bool
    {
        $keyword = $this->removeAccents(strtolower(trim($keyword)));

        // Gérer les mots composés (ex: "heure ouverture")
        if (str_contains($keyword, ' ')) {
            // Pour les expressions multi-mots, vérifier la présence de tous les mots
            $keywordParts = explode(' ', $keyword);
            $allPartsPresent = true;

            foreach ($keywordParts as $part) {
                if (!str_contains($message, $part)) {
                    $allPartsPresent = false;
                    break;
                }
            }

            if ($allPartsPresent) {
                return true;
            }
        }

        // Recherche de mot entier avec délimiteurs
        return preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $message) === 1;
    }

    private function startsWithKeyword(string $message, string $keyword): bool
    {
        $keyword = $this->removeAccents(strtolower($keyword));
        $words = explode(' ', $message);
        return isset($words[0]) && str_contains($words[0], $keyword);
    }

    private function containsPartialKeyword(string $message, string $keyword): bool
    {
        $keyword = $this->removeAccents(strtolower($keyword));
        return str_contains($message, $keyword);
    }

    public function generateTitle(string $firstMessage): string
    {
        $message = strtolower($firstMessage);

        $titlePatterns = [
            'louer|location' => 'Recherche de location',
            'acheter|achat|vente' => 'Projet d\'achat immobilier',
            'prix|tarif|budget' => 'Question sur les prix',
            'visite|visiter' => 'Demande de visite',
            'appartement' => 'Recherche d\'appartement',
            'maison|villa' => 'Recherche de maison',
            'studio' => 'Recherche de studio',
            'terrain' => 'Recherche de terrain',
            'document|dossier' => 'Constitution de dossier',
            'quartier|zone' => 'Question sur les quartiers',
            'réservation|réserver' => 'Demande de réservation',
            'investissement|investir' => 'Projet d\'investissement',
            'colocation' => 'Recherche de colocation',
            'étudiant' => 'Logement étudiant',
            'expatrié|étranger' => 'Installation expatrié',
            'commercial|bureau' => 'Local professionnel',
            'piscine' => 'Bien avec piscine',
            'jardin' => 'Bien avec jardin',
            'parking' => 'Question sur parking',
            'meublé' => 'Location meublée',
            'urgent' => 'Demande urgente',
            'horaire|ouverture' => 'Horaires d\'ouverture',
        ];

        foreach ($titlePatterns as $pattern => $title) {
            $keywords = explode('|', $pattern);
            foreach ($keywords as $keyword) {
                if (str_contains($message, $keyword)) {
                    return $title;
                }
            }
        }

        $maxLength = 50;
        $title = substr($firstMessage, 0, $maxLength);

        if (strlen($firstMessage) > $maxLength) {
            $lastSpace = strrpos($title, ' ');
            if ($lastSpace !== false && $lastSpace > 30) {
                $title = substr($title, 0, $lastSpace);
            }
            $title .= '...';
        }

        return ucfirst($title);
    }

    private function analyzeSentiment(string $message): string
    {
        $positiveWords = ['merci', 'super', 'parfait', 'excellent', 'génial', 'bien', 'sympa', 'content', 'satisfait'];
        $negativeWords = ['problème', 'mauvais', 'nul', 'déçu', 'mécontent', 'pas bien', 'pas bon', 'arnaque'];

        $positiveCount = 0;
        $negativeCount = 0;

        foreach ($positiveWords as $word) {
            if (str_contains($message, $word)) {
                $positiveCount++;
            }
        }

        foreach ($negativeWords as $word) {
            if (str_contains($message, $word)) {
                $negativeCount++;
            }
        }

        if ($positiveCount > $negativeCount) {
            return 'positive';
        } elseif ($negativeCount > $positiveCount) {
            return 'negative';
        }

        return 'neutral';
    }

    public function extractInfo(string $message): array
    {
        $info = [
            'budget' => null,
            'rooms' => null,
            'type' => null,
            'location_intent' => false,
            'purchase_intent' => false,
        ];

        if (preg_match('/(\d+(?:\s?\d+)*)\s*(?:fcfa|cfa|f)/i', $message, $matches)) {
            $info['budget'] = (int) str_replace(' ', '', $matches[1]);
        } elseif (preg_match('/(\d+)\s*(?:millions?|m)/i', $message, $matches)) {
            $info['budget'] = (int) $matches[1] * 1000000;
        }

        if (preg_match('/(\d+)\s*(?:chambre|pièce|bedroom)/i', $message, $matches)) {
            $info['rooms'] = (int) $matches[1];
        } elseif (preg_match('/\b(f|t)([1-6])\b/i', $message, $matches)) {
            $info['rooms'] = (int) $matches[2];
        }

        if (str_contains($message, 'studio')) {
            $info['type'] = 'studio';
        } elseif (str_contains($message, 'appartement') || str_contains($message, 'appart')) {
            $info['type'] = 'appartement';
        } elseif (str_contains($message, 'maison') || str_contains($message, 'villa')) {
            $info['type'] = 'maison';
        } elseif (str_contains($message, 'terrain')) {
            $info['type'] = 'terrain';
        } elseif (str_contains($message, 'bureau') || str_contains($message, 'commercial')) {
            $info['type'] = 'commercial';
        }

        $info['location_intent'] = str_contains($message, 'louer') || str_contains($message, 'location');
        $info['purchase_intent'] = str_contains($message, 'acheter') || str_contains($message, 'achat');

        return $info;
    }

    public function suggestFollowUpQuestions(string $userMessage): array
    {
        $message = strtolower($userMessage);
        $suggestions = [];

        if (str_contains($message, 'prix') || str_contains($message, 'tarif')) {
            $suggestions = [
                "Quel est votre budget maximum ?",
                "Préférez-vous louer ou acheter ?",
                "Dans quel quartier recherchez-vous ?",
            ];
        } elseif (str_contains($message, 'location') || str_contains($message, 'louer')) {
            $suggestions = [
                "Combien de chambres souhaitez-vous ?",
                "Quel est votre budget mensuel ?",
                "Quand souhaitez-vous emménager ?",
            ];
        } elseif (str_contains($message, 'achat') || str_contains($message, 'acheter')) {
            $suggestions = [
                "Quel type de bien recherchez-vous ?",
                "Avez-vous besoin d'un financement ?",
                "Dans quelle zone souhaitez-vous acheter ?",
            ];
        } elseif (str_contains($message, 'visite')) {
            $suggestions = [
                "Quand êtes-vous disponible pour une visite ?",
                "Préférez-vous une visite physique ou virtuelle ?",
                "Pour quel bien souhaitez-vous organiser une visite ?",
            ];
        } elseif (str_contains($message, 'horaire') || str_contains($message, 'ouverture')) {
            $suggestions = [
                "Souhaitez-vous prendre rendez-vous ?",
                "Préférez-vous nous appeler ou venir en agence ?",
                "Avez-vous d'autres questions ?",
            ];
        } elseif (str_contains($message, 'adresse') || str_contains($message, 'où') || str_contains($message, 'située')) {
            $suggestions = [
                "Souhaitez-vous un itinéraire pour venir nous voir ?",
                "Préférez-vous prendre rendez-vous avant de venir ?",
                "Avez-vous besoin d'autres informations ?",
            ];
        } elseif (str_contains($message, 'disponible') || str_contains($message, 'lundi') || str_contains($message, 'mardi') ||
            str_contains($message, 'semaine') || str_contains($message, 'partir de')) {
            $suggestions = [
                "Pour quel type de bien souhaitez-vous un rendez-vous ?",
                "Préférez-vous une visite le matin ou l'après-midi ?",
                "Avez-vous déjà identifié un bien qui vous intéresse ?",
            ];
        } else {
            $suggestions = [
                "Puis-je vous aider à trouver un bien ?",
                "Avez-vous d'autres questions ?",
                "Souhaitez-vous plus d'informations ?",
            ];
        }

        return $suggestions;
    }

    private function isQuestion(string $message): bool
    {
        $questionWords = ['qui', 'que', 'quoi', 'où', 'quand', 'comment', 'combien', 'pourquoi', 'quel', 'quelle'];
        $message = strtolower($message);

        foreach ($questionWords as $word) {
            if (str_starts_with($message, $word)) {
                return true;
            }
        }

        if (str_ends_with(trim($message), '?')) {
            return true;
        }

        return false;
    }

    public function generateEnrichedResponse(string $userMessage, array $conversationHistory = []): array
    {
        $response = $this->generateResponse($userMessage);
        $extractedInfo = $this->extractInfo($userMessage);
        $followUpQuestions = $this->suggestFollowUpQuestions($userMessage);
        $sentiment = $this->analyzeSentiment($userMessage);

        return [
            'response' => $response,
            'extracted_info' => $extractedInfo,
            'follow_up_questions' => $followUpQuestions,
            'sentiment' => $sentiment,
            'is_question' => $this->isQuestion($userMessage),
        ];
    }
}
