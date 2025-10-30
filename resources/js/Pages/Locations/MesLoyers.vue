<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 p-6">
        <div class="max-w-7xl mx-auto">
            <!-- Message si aucune location -->
            <div v-if="locations.length === 0" class="bg-white rounded-2xl shadow-lg p-12 text-center">
                <div class="text-6xl mb-4">🏠</div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Aucune location active</h2>
                <p class="text-gray-600 mb-6">Vous n'avez pas encore de location en cours</p>
                <button
                    @click="router.visit(route('home'))"
                    class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition"
                >
                    Découvrir les biens disponibles
                </button>
            </div>

            <!-- Contenu principal -->
            <div v-else>
                <!-- En-tête -->
                <div class="mb-8">
                    <h1 class="text-4xl font-bold text-gray-900 mb-2">💰 Mes Loyers</h1>
                    <p class="text-gray-600">Gérez vos paiements mensuels en toute simplicité</p>
                </div>

                <!-- Statistiques globales -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                    <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500">
                        <div class="text-sm text-gray-600 mb-1">Locations actives</div>
                        <div class="text-3xl font-bold text-gray-900">{{ statsGlobales.totalLocations }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500">
                        <div class="text-sm text-gray-600 mb-1">Loyers payés</div>
                        <div class="text-3xl font-bold text-green-600">
                            {{ statsGlobales.loyersPayes }}/{{ statsGlobales.totalLoyers }}
                        </div>
                        <div class="text-xs text-gray-500 mt-1">{{ statsGlobales.tauxPaiement }}% complété</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-red-500">
                        <div class="text-sm text-gray-600 mb-1">En retard</div>
                        <div class="text-3xl font-bold text-red-600">{{ statsGlobales.loyersEnRetard }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-purple-500">
                        <div class="text-sm text-gray-600 mb-1">Total payé</div>
                        <div class="text-2xl font-bold text-purple-600">
                            {{ formatMontant(statsGlobales.montantPaye) }} FCFA
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            sur {{ formatMontant(statsGlobales.montantTotal) }} FCFA
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Sélection de la location -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-2xl shadow-lg p-6 sticky top-6">
                            <h2 class="text-xl font-bold text-gray-800 mb-4">Mes Locations</h2>
                            <div class="space-y-3">
                                <button
                                    v-for="loc in locations"
                                    :key="loc.id"
                                    @click="selectedLocation = loc.id"
                                    :class="[
                    'w-full text-left p-4 rounded-xl transition-all',
                    selectedLocation === loc.id
                      ? 'bg-blue-600 text-white shadow-lg scale-105'
                      : 'bg-gray-50 hover:bg-gray-100 text-gray-800'
                  ]"
                                >
                                    <div class="font-semibold text-sm mb-1">
                                        {{ loc.bien?.titre || 'Location' }}
                                    </div>
                                    <div :class="['text-xs mb-2', selectedLocation === loc.id ? 'text-blue-100' : 'text-gray-500']">
                                        {{ loc.bien?.adresse }}, {{ loc.bien?.ville }}
                                    </div>
                                    <div class="flex items-center justify-between text-xs">
                    <span :class="selectedLocation === loc.id ? 'text-blue-100' : 'text-gray-600'">
                      {{ formatMontant(loc.loyer_mensuel) }} FCFA/mois
                    </span>
                                        <span
                                            v-if="loc.statistiques.mois_en_retard > 0"
                                            :class="[
                        'px-2 py-1 rounded',
                        selectedLocation === loc.id
                          ? 'bg-red-500 text-white'
                          : 'bg-red-100 text-red-800'
                      ]"
                                        >
                      {{ loc.statistiques.mois_en_retard }} en retard
                    </span>
                                    </div>
                                    <div class="mt-2 bg-gray-200 rounded-full h-2">
                                        <div
                                            :class="[
                        'h-2 rounded-full',
                        selectedLocation === loc.id ? 'bg-white' : 'bg-blue-500'
                      ]"
                                            :style="{ width: `${loc.statistiques.taux_paiement}%` }"
                                        />
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Détails et liste des loyers -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Informations de la location -->
                        <div
                            v-if="location"
                            class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-2xl shadow-lg p-6 text-white"
                        >
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h2 class="text-2xl font-bold mb-2">{{ location.bien?.titre }}</h2>
                                    <p class="text-blue-100">{{ location.bien?.adresse }}, {{ location.bien?.ville }}</p>
                                </div>
                                <div class="text-right">
                                    <div class="text-3xl font-bold">{{ formatMontant(location.loyer_mensuel) }}</div>
                                    <div class="text-sm text-blue-100">FCFA / mois</div>
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-4 pt-4 border-t border-blue-400">
                                <div>
                                    <div class="text-sm text-blue-100">Début</div>
                                    <div class="font-semibold">{{ formatDate(location.date_debut) }}</div>
                                </div>
                                <div>
                                    <div class="text-sm text-blue-100">Fin</div>
                                    <div class="font-semibold">{{ formatDate(location.date_fin) }}</div>
                                </div>
                                <div>
                                    <div class="text-sm text-blue-100">Type</div>
                                    <div class="font-semibold text-sm">
                                        {{ location.type_contrat_info?.label || location.type_contrat }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filtres et recherche -->
                        <div class="bg-white rounded-2xl shadow-lg p-6">
                            <div class="flex flex-col md:flex-row gap-4 mb-6">
                                <div class="flex-1">
                                    <input
                                        v-model="searchTerm"
                                        type="text"
                                        placeholder="Rechercher un mois..."
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    />
                                </div>
                                <div class="flex gap-2 flex-wrap">
                                    <button
                                        v-for="filtre in filtres"
                                        :key="filtre.value"
                                        @click="filtreStatut = filtre.value"
                                        :class="[
                      'px-4 py-2 rounded-lg font-medium transition-all',
                      filtreStatut === filtre.value
                        ? 'bg-blue-600 text-white shadow-md'
                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                    ]"
                                    >
                                        {{ filtre.icon }} {{ filtre.label }}
                                    </button>
                                </div>
                            </div>

                            <!-- Liste des loyers -->
                            <div class="space-y-3">
                                <div v-if="loyersFiltres.length === 0" class="text-center py-12 text-gray-500">
                                    <div class="text-4xl mb-2">🔍</div>
                                    <p>Aucun loyer trouvé avec ces filtres</p>
                                </div>
                                <div
                                    v-else
                                    v-for="mois in loyersFiltres"
                                    :key="mois.mois"
                                    :class="[
                    'p-5 rounded-xl border-2 transition-all hover:shadow-md',
                    mois.statut === 'en_retard'
                      ? 'border-red-300 bg-red-50'
                      : mois.statut === 'paye'
                      ? 'border-green-300 bg-green-50'
                      : 'border-gray-200 bg-white'
                  ]"
                                >
                                    <div class="flex justify-between items-start mb-3">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3 mb-2">
                                                <h3 class="text-lg font-bold text-gray-900">{{ mois.mois_libelle }}</h3>
                                                <span
                                                    :class="[
                            'px-3 py-1 rounded-full text-xs font-medium',
                            getBadgeStatutClass(mois.statut)
                          ]"
                                                >
                          {{ getBadgeStatutLabel(mois.statut) }}
                        </span>
                                            </div>
                                            <div class="text-sm text-gray-600 space-y-1">
                                                <div>
                                                    📅 Période: {{ formatDate(mois.date_debut) }} - {{ formatDate(mois.date_fin) }}
                                                </div>
                                                <div :class="mois.jours_retard > 0 ? 'text-red-600 font-semibold' : ''">
                                                    ⏰ Échéance: {{ formatDate(mois.date_echeance) }}
                                                    <span v-if="mois.jours_retard > 0" class="ml-2">
                            ({{ mois.jours_retard }} jour{{ mois.jours_retard > 1 ? 's' : '' }} de retard)
                          </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-2xl font-bold text-gray-900">
                                                {{ formatMontant(mois.montant) }}
                                            </div>
                                            <div class="text-sm text-gray-500">FCFA</div>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex justify-end gap-2 pt-3 border-t border-gray-200">
                                        <div v-if="mois.paye" class="flex items-center gap-2 text-green-600 font-medium">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                            Loyer payé
                                        </div>
                                        <div v-else-if="mois.statut === 'futur'" class="text-gray-500 text-sm">
                                            Paiement disponible prochainement
                                        </div>
                                        <button
                                            v-else
                                            @click="handlePayerLoyer(mois)"
                                            :disabled="loading"
                                            :class="[
                        'px-6 py-2 rounded-lg font-semibold transition-all',
                        mois.statut === 'en_retard'
                          ? 'bg-red-600 hover:bg-red-700 text-white shadow-lg'
                          : 'bg-blue-600 hover:bg-blue-700 text-white',
                        loading ? 'opacity-50 cursor-not-allowed' : ''
                      ]"
                                        >
                      <span v-if="loading" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                          <circle
                              class="opacity-25"
                              cx="12"
                              cy="12"
                              r="10"
                              stroke="currentColor"
                              stroke-width="4"
                              fill="none"
                          />
                          <path
                              class="opacity-75"
                              fill="currentColor"
                              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                          />
                        </svg>
                        Traitement...
                      </span>
                                            <span v-else>
                        💳 Payer {{ mois.statut === 'en_retard' ? 'maintenant' : 'ce loyer' }}
                      </span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Statistiques de la location -->
                        <div v-if="location" class="bg-white rounded-2xl shadow-lg p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-4">📊 Statistiques</h3>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                <div class="p-4 bg-blue-50 rounded-lg">
                                    <div class="text-sm text-gray-600 mb-1">Total mois</div>
                                    <div class="text-2xl font-bold text-blue-600">
                                        {{ location.statistiques.total_mois }}
                                    </div>
                                </div>
                                <div class="p-4 bg-green-50 rounded-lg">
                                    <div class="text-sm text-gray-600 mb-1">Mois payés</div>
                                    <div class="text-2xl font-bold text-green-600">
                                        {{ location.statistiques.mois_payes }}
                                    </div>
                                </div>
                                <div class="p-4 bg-red-50 rounded-lg">
                                    <div class="text-sm text-gray-600 mb-1">En retard</div>
                                    <div class="text-2xl font-bold text-red-600">
                                        {{ location.statistiques.mois_en_retard }}
                                    </div>
                                </div>
                                <div class="p-4 bg-purple-50 rounded-lg">
                                    <div class="text-sm text-gray-600 mb-1">Total dû</div>
                                    <div class="text-xl font-bold text-purple-600">
                                        {{ formatMontant(location.statistiques.montant_total) }} F
                                    </div>
                                </div>
                                <div class="p-4 bg-indigo-50 rounded-lg">
                                    <div class="text-sm text-gray-600 mb-1">Déjà payé</div>
                                    <div class="text-xl font-bold text-indigo-600">
                                        {{ formatMontant(location.statistiques.montant_paye) }} F
                                    </div>
                                </div>
                                <div class="p-4 bg-orange-50 rounded-lg">
                                    <div class="text-sm text-gray-600 mb-1">Restant</div>
                                    <div class="text-xl font-bold text-orange-600">
                                        {{ formatMontant(location.statistiques.montant_restant) }} F
                                    </div>
                                </div>
                            </div>

                            <!-- Barre de progression -->
                            <div class="mt-6">
                                <div class="flex justify-between text-sm text-gray-600 mb-2">
                                    <span>Progression des paiements</span>
                                    <span class="font-bold">{{ location.statistiques.taux_paiement }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-4">
                                    <div
                                        class="bg-gradient-to-r from-blue-500 to-purple-600 h-4 rounded-full transition-all duration-500 flex items-center justify-end pr-2"
                                        :style="{ width: `${location.statistiques.taux_paiement}%` }"
                                    >
                    <span
                        v-if="location.statistiques.taux_paiement > 10"
                        class="text-white text-xs font-bold"
                    >
                      {{ location.statistiques.mois_payes }}/{{ location.statistiques.total_mois }}
                    </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bouton retour -->
                <div class="mt-8 text-center">
                    <button
                        @click="router.visit(route('locations.index'))"
                        class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg transition"
                    >
                        ← Retour aux locations
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

// Props
const props = defineProps({
    locations: {
        type: Array,
        required: true
    },
    user: {
        type: Object,
        required: true
    }
});

// État local
const selectedLocation = ref(props.locations[0]?.id || null);
const filtreStatut = ref('tous');
const searchTerm = ref('');
const loading = ref(false);

// Filtres disponibles
const filtres = [
    { value: 'tous', label: 'Tous', icon: '📋' },
    { value: 'a_payer', label: 'À payer', icon: '⏰' },
    { value: 'paye', label: 'Payés', icon: '✓' },
    { value: 'en_retard', label: 'En retard', icon: '⚠️' }
];

// Location sélectionnée
const location = computed(() => {
    return props.locations.find(l => l.id === selectedLocation.value) || props.locations[0];
});

// Loyers filtrés
const loyersFiltres = computed(() => {
    if (!location.value) return [];

    let loyers = location.value.mois_loyers || [];

    // Filtre par statut
    if (filtreStatut.value !== 'tous') {
        loyers = loyers.filter(l => {
            if (filtreStatut.value === 'a_payer') {
                return ['en_attente', 'en_cours'].includes(l.statut);
            }
            return l.statut === filtreStatut.value;
        });
    }

    // Recherche par mois
    if (searchTerm.value) {
        loyers = loyers.filter(l =>
            l.mois_libelle.toLowerCase().includes(searchTerm.value.toLowerCase())
        );
    }

    return loyers;
});

// Statistiques globales
const statsGlobales = computed(() => {
    const totalLocations = props.locations.length;
    const totalLoyers = props.locations.reduce((sum, l) => sum + (l.statistiques?.total_mois || 0), 0);
    const loyersPayes = props.locations.reduce((sum, l) => sum + (l.statistiques?.mois_payes || 0), 0);
    const loyersEnRetard = props.locations.reduce((sum, l) => sum + (l.statistiques?.mois_en_retard || 0), 0);
    const montantTotal = props.locations.reduce((sum, l) => sum + (l.statistiques?.montant_total || 0), 0);
    const montantPaye = props.locations.reduce((sum, l) => sum + (l.statistiques?.montant_paye || 0), 0);

    return {
        totalLocations,
        totalLoyers,
        loyersPayes,
        loyersEnRetard,
        montantTotal,
        montantPaye,
        tauxPaiement: totalLoyers > 0 ? ((loyersPayes / totalLoyers) * 100).toFixed(1) : 0
    };
});

// Méthodes
const formatMontant = (montant) => {
    return new Intl.NumberFormat('fr-FR').format(montant);
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR');
};

const getBadgeStatutClass = (statut) => {
    const classes = {
        paye: 'bg-green-100 text-green-800',
        en_cours: 'bg-blue-100 text-blue-800',
        en_attente: 'bg-yellow-100 text-yellow-800',
        en_retard: 'bg-red-100 text-red-800',
        futur: 'bg-gray-100 text-gray-600'
    };
    return classes[statut] || classes.futur;
};

const getBadgeStatutLabel = (statut) => {
    const labels = {
        paye: '✓ Payé',
        en_cours: 'En cours',
        en_attente: 'En attente',
        en_retard: '⚠ En retard',
        futur: 'À venir'
    };
    return labels[statut] || statut;
};

const handlePayerLoyer = async (mois) => {
    if (!confirm(`Confirmer le paiement du loyer de ${mois.mois_libelle} ?`)) {
        return;
    }

    loading.value = true;

    try {
        const response = await fetch(
            route('locations.payer-loyer', location.value.id),
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ mois: mois.mois })
            }
        );

        const data = await response.json();

        if (data.success) {
            window.location.href = data.redirect_url;
        } else {
            alert(data.message || 'Erreur lors de la création du paiement');
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Une erreur est survenue');
    } finally {
        loading.value = false;
    }
};
</script>
