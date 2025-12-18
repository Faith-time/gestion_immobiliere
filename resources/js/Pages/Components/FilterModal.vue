<template>
    <Teleport to="body">
        <!-- Backdrop -->
        <div
            class="modal-backdrop fade show"
            @click="closeModal"
        ></div>

        <!-- Modal -->
        <div
            class="modal fade show d-block"
            tabindex="-1"
            role="dialog"
            @click.self="closeModal"
        >
            <div class="modal-dialog modal-lg modal-dialog-centered" @click.stop>
                <div class="modal-content">
                    <!-- Header -->
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i :class="modalIcon" class="me-2"></i>
                            {{ modalTitle }}
                        </h5>
                        <button
                            type="button"
                            class="btn-close btn-close-white"
                            @click="closeModal"
                            aria-label="Fermer"
                        ></button>
                    </div>

                    <!-- Body -->
                    <div class="modal-body">
                        <!-- Price Filter -->
                        <div v-if="activeFilter === 'price'" class="filter-section">
                            <p class="info-text">
                                <i class="fas fa-info-circle me-2"></i>
                                Définissez votre budget pour trouver les biens qui vous conviennent
                            </p>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="minPrice" class="form-label fw-bold">
                                        <i class="fas fa-tag me-1"></i>
                                        Prix minimum (FCFA)
                                    </label>
                                    <input
                                        id="minPrice"
                                        type="number"
                                        class="form-control form-control-lg"
                                        v-model.number="localFilters.minPrice"
                                        placeholder="Ex: 5 000 000"
                                        min="0"
                                        step="100000"
                                        @input="validatePriceRange"
                                    >
                                </div>
                                <div class="col-md-6">
                                    <label for="maxPrice" class="form-label fw-bold">
                                        <i class="fas fa-tag me-1"></i>
                                        Prix maximum (FCFA)
                                    </label>
                                    <input
                                        id="maxPrice"
                                        type="number"
                                        class="form-control form-control-lg"
                                        v-model.number="localFilters.maxPrice"
                                        placeholder="Ex: 50 000 000"
                                        min="0"
                                        step="100000"
                                        @input="validatePriceRange"
                                    >
                                </div>
                            </div>

                            <div v-if="priceError" class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                {{ priceError }}
                            </div>

                            <!-- Quick Price Ranges -->
                            <div class="quick-select">
                                <label class="form-label fw-bold mb-3">
                                    <i class="fas fa-bolt me-1"></i>
                                    Sélection rapide :
                                </label>
                                <div class="d-flex flex-wrap gap-2">
                                    <button
                                        v-for="range in priceRanges"
                                        :key="range.label"
                                        type="button"
                                        class="btn btn-outline-primary"
                                        @click="selectPriceRange(range)"
                                    >
                                        {{ range.label }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Location Filter -->
                        <div v-else-if="activeFilter === 'location'" class="filter-section">
                            <p class="info-text">
                                <i class="fas fa-info-circle me-2"></i>
                                Trouvez un bien dans votre quartier ou ville préférée
                            </p>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="city" class="form-label fw-bold">
                                        <i class="fas fa-city me-1"></i>
                                        Ville
                                    </label>
                                    <input
                                        id="city"
                                        type="text"
                                        class="form-control form-control-lg"
                                        v-model="localFilters.city"
                                        placeholder="Ex: Dakar, Thiès..."
                                        list="cityList"
                                    >
                                    <datalist id="cityList">
                                        <option value="Dakar"></option>
                                        <option value="Thiès"></option>
                                        <option value="Saint-Louis"></option>
                                        <option value="Rufisque"></option>
                                        <option value="Pikine"></option>
                                        <option value="Guédiawaye"></option>
                                        <option value="Mbour"></option>
                                        <option value="Kaolack"></option>
                                        <option value="Ziguinchor"></option>
                                        <option value="Louga"></option>
                                    </datalist>
                                </div>
                                <div class="col-md-6">
                                    <label for="address" class="form-label fw-bold">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        Quartier / Adresse
                                    </label>
                                    <input
                                        id="address"
                                        type="text"
                                        class="form-control form-control-lg"
                                        v-model="localFilters.address"
                                        placeholder="Ex: Almadies, Plateau..."
                                    >
                                </div>
                            </div>

                            <!-- Popular Locations -->
                            <div class="quick-select mt-4">
                                <label class="form-label fw-bold mb-3">
                                    <i class="fas fa-star me-1"></i>
                                    Quartiers populaires :
                                </label>
                                <div class="d-flex flex-wrap gap-2">
                                    <button
                                        v-for="location in popularLocations"
                                        :key="location"
                                        type="button"
                                        class="btn btn-outline-success btn-sm"
                                        @click="selectLocation(location)"
                                    >
                                        {{ location }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Features Filter -->
                        <div v-else-if="activeFilter === 'features'" class="filter-section">
                            <p class="info-text">
                                <i class="fas fa-info-circle me-2"></i>
                                Précisez les caractéristiques du bien recherché
                            </p>

                            <div class="row g-4">
                                <div class="col-md-4">
                                    <label for="rooms" class="form-label fw-bold">
                                        <i class="fas fa-bed me-1"></i>
                                        Chambres
                                    </label>
                                    <select
                                        id="rooms"
                                        class="form-select form-select-lg"
                                        v-model="localFilters.rooms"
                                    >
                                        <option value="">Toutes</option>
                                        <option value="1">1 chambre</option>
                                        <option value="2">2 chambres</option>
                                        <option value="3">3 chambres</option>
                                        <option value="4">4 chambres</option>
                                        <option value="5">5+ chambres</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="bathrooms" class="form-label fw-bold">
                                        <i class="fas fa-bath me-1"></i>
                                        Salles de bain
                                    </label>
                                    <select
                                        id="bathrooms"
                                        class="form-select form-select-lg"
                                        v-model="localFilters.bathrooms"
                                    >
                                        <option value="">Toutes</option>
                                        <option value="1">1 salle de bain</option>
                                        <option value="2">2 salles de bain</option>
                                        <option value="3">3 salles de bain</option>
                                        <option value="4">4+ salles de bain</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="floors" class="form-label fw-bold">
                                        <i class="fas fa-building me-1"></i>
                                        Étages
                                    </label>
                                    <select
                                        id="floors"
                                        class="form-select form-select-lg"
                                        v-model="localFilters.floors"
                                    >
                                        <option value="">Tous</option>
                                        <option value="1">1 étage</option>
                                        <option value="2">2 étages</option>
                                        <option value="3">3 étages</option>
                                        <option value="4">4+ étages</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Features Summary -->
                            <div v-if="hasFeatureFilters" class="alert alert-info mt-4">
                                <strong><i class="fas fa-check-circle me-2"></i>Recherche :</strong>
                                Bien avec
                                <span v-if="localFilters.rooms"> {{ localFilters.rooms }} chambre(s)</span>
                                <span v-if="localFilters.bathrooms">, {{ localFilters.bathrooms }} salle(s) de bain</span>
                                <span v-if="localFilters.floors">, {{ localFilters.floors }} étage(s)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-outline-secondary btn-lg"
                            @click="handleReset"
                        >
                            <i class="fas fa-undo me-2"></i>
                            Réinitialiser
                        </button>
                        <button
                            type="button"
                            class="btn btn-primary btn-lg"
                            @click="handleApply"
                            :disabled="!isValid"
                        >
                            <i class="fas fa-check me-2"></i>
                            Appliquer les filtres
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

// Props
const props = defineProps({
    activeFilter: {
        type: String,
        required: true
    },
    filters: {
        type: Object,
        required: true
    }
})

// Emits
const emit = defineEmits(['close', 'apply', 'reset'])

// Local state
const localFilters = ref({
    minPrice: '',
    maxPrice: '',
    city: '',
    address: '',
    rooms: '',
    bathrooms: '',
    floors: ''
})

const priceError = ref('')

// Watch for prop changes
watch(() => props.filters, (newFilters) => {
    localFilters.value = {
        minPrice: newFilters.minPrice || '',
        maxPrice: newFilters.maxPrice || '',
        city: newFilters.city || '',
        address: newFilters.address || '',
        rooms: newFilters.rooms || '',
        bathrooms: newFilters.bathrooms || '',
        floors: newFilters.floors || ''
    }
}, { immediate: true, deep: true })

// Data
const priceRanges = [
    { label: '< 10M', min: 0, max: 10000000 },
    { label: '10M - 20M', min: 10000000, max: 20000000 },
    { label: '20M - 50M', min: 20000000, max: 50000000 },
    { label: '50M - 100M', min: 50000000, max: 100000000 },
    { label: '> 100M', min: 100000000, max: null }
]

const popularLocations = [
    'Almadies', 'Plateau', 'Mermoz', 'Ngor', 'Ouakam',
    'Point E', 'Sacré-Cœur', 'Fann', 'HLM', 'Parcelles Assainies'
]

// Computed
const modalTitle = computed(() => {
    const titles = {
        price: 'Filtrer par prix',
        location: 'Filtrer par localisation',
        features: 'Filtrer par caractéristiques'
    }
    return titles[props.activeFilter] || 'Filtres'
})

const modalIcon = computed(() => {
    const icons = {
        price: 'fas fa-euro-sign',
        location: 'fas fa-map-marker-alt',
        features: 'fas fa-home'
    }
    return icons[props.activeFilter] || 'fas fa-filter'
})

const hasFeatureFilters = computed(() => {
    return localFilters.value.rooms || localFilters.value.bathrooms || localFilters.value.floors
})

const isValid = computed(() => {
    if (props.activeFilter === 'price') {
        return !priceError.value
    }
    return true
})

// Methods
const validatePriceRange = () => {
    const min = Number(localFilters.value.minPrice)
    const max = Number(localFilters.value.maxPrice)

    priceError.value = ''

    if (min && max && min > max) {
        priceError.value = 'Le prix minimum ne peut pas être supérieur au prix maximum'
    }

    if (min && min < 0) {
        priceError.value = 'Le prix minimum doit être positif'
    }

    if (max && max < 0) {
        priceError.value = 'Le prix maximum doit être positif'
    }
}

const selectPriceRange = (range) => {
    localFilters.value.minPrice = range.min
    localFilters.value.maxPrice = range.max
    validatePriceRange()
}

const selectLocation = (location) => {
    localFilters.value.address = location
}

const closeModal = () => {
    emit('close')
}

const handleApply = () => {
    if (!isValid.value) {
        return
    }

    // Clean up the filters - remove empty values
    const cleanedFilters = {}

    Object.keys(localFilters.value).forEach(key => {
        const value = localFilters.value[key]
        if (value !== '' && value !== null && value !== undefined) {
            cleanedFilters[key] = value
        }
    })

    console.log('FilterModal: Applying filters', cleanedFilters)
    emit('apply', cleanedFilters)
}

const handleReset = () => {
    localFilters.value = {
        minPrice: '',
        maxPrice: '',
        city: '',
        address: '',
        rooms: '',
        bathrooms: '',
        floors: ''
    }
    priceError.value = ''
    emit('reset')
}
</script>

<style scoped>
/* Backdrop */
.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background-color: rgba(0, 0, 0, 0.6);
    z-index: 1040;
    backdrop-filter: blur(2px);
}

/* Modal */
.modal {
    z-index: 1050;
    display: flex !important;
    align-items: center;
    justify-content: center;
}

.modal-dialog {
    max-width: 700px;
    margin: 1rem;
}

.modal-content {
    border: none;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: modalSlideIn 0.3s ease;
    overflow: hidden;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(-50px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

/* Header */
.modal-header {
    background: linear-gradient(135deg, #006064, #00838f);
    color: white;
    padding: 1.5rem 2rem;
    border: none;
}

.modal-title {
    font-weight: 700;
    font-size: 1.5rem;
    margin: 0;
}

.btn-close-white {
    filter: brightness(0) invert(1);
    opacity: 0.8;
}

.btn-close-white:hover {
    opacity: 1;
}

/* Body */
.modal-body {
    padding: 2rem;
    background: #f8f9fa;
}

.filter-section {
    min-height: 300px;
}

.info-text {
    color: #6c757d;
    font-size: 0.95rem;
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: white;
    border-radius: 8px;
    border-left: 4px solid #006064;
}

/* Form elements */
.form-label {
    color: #333;
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
}

.form-control,
.form-select {
    border: 2px solid #dee2e6;
    border-radius: 10px;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
    font-size: 1rem;
}

.form-control-lg,
.form-select-lg {
    padding: 1rem 1.25rem;
    font-size: 1.1rem;
}

.form-control:focus,
.form-select:focus {
    border-color: #006064;
    box-shadow: 0 0 0 0.25rem rgba(0, 96, 100, 0.15);
    background: white;
}

/* Quick select */
.quick-select {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    margin-top: 1.5rem;
}

.btn-outline-primary,
.btn-outline-success {
    border-width: 2px;
    border-radius: 20px;
    padding: 0.5rem 1rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-outline-primary {
    border-color: #006064;
    color: #006064;
}

.btn-outline-primary:hover {
    background: #006064;
    border-color: #006064;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 96, 100, 0.3);
}

.btn-outline-success {
    border-color: #28a745;
    color: #28a745;
}

.btn-outline-success:hover {
    background: #28a745;
    border-color: #28a745;
    color: white;
    transform: translateY(-2px);
}

/* Alerts */
.alert {
    border-radius: 10px;
    border: none;
    padding: 1rem 1.25rem;
}

.alert-warning {
    background: #fff3cd;
    color: #856404;
}

.alert-info {
    background: #d1ecf1;
    color: #0c5460;
}

/* Footer */
.modal-footer {
    padding: 1.5rem 2rem;
    border-top: 1px solid #dee2e6;
    background: white;
}

.btn-lg {
    padding: 0.75rem 2rem;
    border-radius: 10px;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.btn-primary {
    background: linear-gradient(135deg, #006064, #00838f);
    border: none;
}

.btn-primary:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 96, 100, 0.4);
}

.btn-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-outline-secondary {
    border: 2px solid #6c757d;
    color: #6c757d;
}

.btn-outline-secondary:hover {
    background: #6c757d;
    color: white;
    transform: translateY(-2px);
}

/* Responsive */
@media (max-width: 768px) {
    .modal-dialog {
        margin: 0.5rem;
        max-width: calc(100% - 1rem);
    }

    .modal-body {
        padding: 1.5rem;
    }

    .modal-header,
    .modal-footer {
        padding: 1rem 1.5rem;
    }

    .modal-title {
        font-size: 1.25rem;
    }

    .btn-lg {
        padding: 0.65rem 1.5rem;
        font-size: 0.95rem;
    }

    .filter-section {
        min-height: auto;
    }
}
</style>
