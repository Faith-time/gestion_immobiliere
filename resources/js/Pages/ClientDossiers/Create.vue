<template>
    <Transition name="modal">
        <div v-if="show" class="modal-overlay" @click.self="closeModal">
            <div class="modal-container">
                <!-- Header -->
                <div class="modal-header">
                    <div class="header-content">
                        <div class="header-icon">
                            <i class="fas fa-file-signature"></i>
                        </div>
                        <div>
                            <h2 class="modal-title">Fiche de Demande de Location</h2>
                            <p class="modal-subtitle">CAURIS IMMO - {{ currentDate }}</p>
                        </div>
                    </div>
                    <button @click="closeModal" class="close-button">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Progress Indicator -->
                <div class="progress-indicator">
                    <div class="progress-bar">
                        <div class="progress-fill" :style="{ width: progressPercentage + '%' }"></div>
                    </div>
                    <div class="progress-steps">
                        <div
                            v-for="(step, index) in steps"
                            :key="index"
                            :class="['progress-step', { active: currentStep === index, completed: currentStep > index }]"
                        >
                            <div class="step-circle">
                                <i v-if="currentStep > index" class="fas fa-check"></i>
                                <span v-else>{{ index + 1 }}</span>
                            </div>
                            <span class="step-label">{{ step }}</span>
                        </div>
                    </div>
                </div>

                <!-- Body -->
                <div class="modal-body">
                    <form @submit.prevent="submitForm">

                        <!-- Étape 1: Informations Personnelles -->
                        <div v-show="currentStep === 0" class="form-step">
                            <div class="step-header">
                                <i class="fas fa-user-circle"></i>
                                <h3>Informations Personnelles</h3>
                            </div>

                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-user"></i>
                                        Nom <span class="required">*</span>
                                    </label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        required
                                        class="form-input"
                                        :class="{ 'input-error': form.errors.name }"
                                        placeholder="Entrez votre nom"
                                    />
                                    <span v-if="form.errors.name" class="error-message">{{ form.errors.name }}</span>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-user"></i>
                                        Prénom <span class="required">*</span>
                                    </label>
                                    <input
                                        v-model="form.prenom"
                                        type="text"
                                        required
                                        class="form-input"
                                        :class="{ 'input-error': form.errors.prenom }"
                                        placeholder="Entrez votre prénom"
                                    />
                                    <span v-if="form.errors.prenom" class="error-message">{{ form.errors.prenom }}</span>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-envelope"></i>
                                        Email <span class="required">*</span>
                                    </label>
                                    <input
                                        v-model="form.email"
                                        type="email"
                                        required
                                        class="form-input"
                                        :class="{ 'input-error': form.errors.email }"
                                        placeholder="exemple@email.com"
                                    />
                                    <span v-if="form.errors.email" class="error-message">{{ form.errors.email }}</span>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-phone"></i>
                                        Téléphone <span class="required">*</span>
                                    </label>
                                    <input
                                        v-model="form.telephone"
                                        type="tel"
                                        required
                                        class="form-input"
                                        :class="{ 'input-error': form.errors.telephone }"
                                        placeholder="+221 XX XXX XX XX"
                                    />
                                    <span v-if="form.errors.telephone" class="error-message">{{ form.errors.telephone }}</span>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-lock"></i>
                                        Mot de passe <span class="required">*</span>
                                    </label>
                                    <div class="password-input">
                                        <input
                                            v-model="form.password"
                                            :type="showPassword ? 'text' : 'password'"
                                            required
                                            class="form-input"
                                            :class="{ 'input-error': form.errors.password }"
                                            placeholder="Minimum 8 caractères"
                                        />
                                        <button
                                            type="button"
                                            @click="showPassword = !showPassword"
                                            class="password-toggle"
                                        >
                                            <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                                        </button>
                                    </div>
                                    <p class="input-hint">Créez un mot de passe sécurisé pour accéder à votre espace client</p>
                                    <span v-if="form.errors.password" class="error-message">{{ form.errors.password }}</span>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-lock"></i>
                                        Confirmer le mot de passe <span class="required">*</span>
                                    </label>
                                    <input
                                        v-model="form.password_confirmation"
                                        type="password"
                                        required
                                        class="form-input"
                                        placeholder="Retapez votre mot de passe"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Étape 2: Informations Complémentaires -->
                        <div v-show="currentStep === 1" class="form-step">
                            <div class="step-header">
                                <i class="fas fa-id-card"></i>
                                <h3>Informations Complémentaires</h3>
                            </div>

                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-briefcase"></i>
                                        Profession
                                    </label>
                                    <input
                                        v-model="form.profession"
                                        type="text"
                                        class="form-input"
                                        placeholder="Ex: Développeur web"
                                    />
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-id-badge"></i>
                                        Numéro CNI
                                    </label>
                                    <input
                                        v-model="form.numero_cni"
                                        type="text"
                                        class="form-input"
                                        placeholder="Numéro de votre carte d'identité"
                                    />
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-user-friends"></i>
                                        Personne à contacter (urgence)
                                    </label>
                                    <input
                                        v-model="form.personne_contact"
                                        type="text"
                                        class="form-input"
                                        placeholder="Nom de la personne"
                                    />
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-phone"></i>
                                        Téléphone du contact
                                    </label>
                                    <input
                                        v-model="form.telephone_contact"
                                        type="tel"
                                        class="form-input"
                                        placeholder="+221 XX XXX XX XX"
                                    />
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-users"></i>
                                        Nombre de personnes à loger
                                    </label>
                                    <input
                                        v-model.number="form.nombre_personnes"
                                        type="number"
                                        min="1"
                                        class="form-input"
                                        placeholder="Ex: 3"
                                    />
                                </div>
                            </div>

                            <!-- Revenus mensuels -->
                            <div class="form-group mt-4">
                                <label class="form-label">
                                    <i class="fas fa-money-bill-wave"></i>
                                    Revenus mensuels
                                </label>
                                <div class="radio-grid">
                                    <label class="radio-card">
                                        <input type="radio" v-model="form.revenus_mensuels" value="plus_100000" />
                                        <div class="radio-content">
                                            <i class="fas fa-coins"></i>
                                            <span>+ 100K FCFA</span>
                                        </div>
                                    </label>
                                    <label class="radio-card">
                                        <input type="radio" v-model="form.revenus_mensuels" value="plus_200000" />
                                        <div class="radio-content">
                                            <i class="fas fa-money-bill"></i>
                                            <span>+ 200K FCFA</span>
                                        </div>
                                    </label>
                                    <label class="radio-card">
                                        <input type="radio" v-model="form.revenus_mensuels" value="plus_300000" />
                                        <div class="radio-content">
                                            <i class="fas fa-wallet"></i>
                                            <span>+ 300K FCFA</span>
                                        </div>
                                    </label>
                                    <label class="radio-card">
                                        <input type="radio" v-model="form.revenus_mensuels" value="plus_500000" />
                                        <div class="radio-content">
                                            <i class="fas fa-hand-holding-usd"></i>
                                            <span>+ 500K FCFA</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Situation familiale -->
                            <div class="form-group mt-4">
                                <label class="form-label">
                                    <i class="fas fa-heart"></i>
                                    Situation familiale
                                </label>
                                <div class="radio-grid-2">
                                    <label class="radio-card">
                                        <input type="radio" v-model="form.situation_familiale" value="celibataire" />
                                        <div class="radio-content">
                                            <i class="fas fa-user"></i>
                                            <span>Célibataire</span>
                                        </div>
                                    </label>
                                    <label class="radio-card">
                                        <input type="radio" v-model="form.situation_familiale" value="marie" />
                                        <div class="radio-content">
                                            <i class="fas fa-ring"></i>
                                            <span>Marié(e)</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Étape 3: Logement Recherché -->
                        <div v-show="currentStep === 2" class="form-step">
                            <div class="step-header">
                                <i class="fas fa-home"></i>
                                <h3>Logement Recherché</h3>
                            </div>

                            <!-- Type de logement -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-building"></i>
                                    Type de logement
                                </label>
                                <div class="checkbox-grid">
                                    <label class="checkbox-card">
                                        <input type="checkbox" value="chambre_simple" v-model="form.type_logement" />
                                        <div class="checkbox-content">
                                            <i class="fas fa-bed"></i>
                                            <span>Chambre simple</span>
                                        </div>
                                    </label>
                                    <label class="checkbox-card">
                                        <input type="checkbox" value="salon" v-model="form.type_logement" />
                                        <div class="checkbox-content">
                                            <i class="fas fa-couch"></i>
                                            <span>Salon</span>
                                        </div>
                                    </label>
                                    <label class="checkbox-card">
                                        <input type="checkbox" value="studio" v-model="form.type_logement" />
                                        <div class="checkbox-content">
                                            <i class="fas fa-door-open"></i>
                                            <span>Studio</span>
                                        </div>
                                    </label>
                                    <label class="checkbox-card">
                                        <input type="checkbox" value="2_chambres_salon" v-model="form.type_logement" />
                                        <div class="checkbox-content">
                                            <i class="fas fa-home"></i>
                                            <span>2 Chambres salon</span>
                                        </div>
                                    </label>
                                    <label class="checkbox-card">
                                        <input type="checkbox" value="3_chambres_salon" v-model="form.type_logement" />
                                        <div class="checkbox-content">
                                            <i class="fas fa-house-user"></i>
                                            <span>3 Chambres Salon</span>
                                        </div>
                                    </label>
                                    <label class="checkbox-card">
                                        <input type="checkbox" value="4_chambres_salon" v-model="form.type_logement" />
                                        <div class="checkbox-content">
                                            <i class="fas fa-city"></i>
                                            <span>4 Chambres salon</span>
                                        </div>
                                    </label>
                                    <label class="checkbox-card">
                                        <input type="checkbox" value="magasin" v-model="form.type_logement" />
                                        <div class="checkbox-content">
                                            <i class="fas fa-store"></i>
                                            <span>Magasin</span>
                                        </div>
                                    </label>
                                    <label class="checkbox-card">
                                        <input type="checkbox" value="autres" v-model="form.type_logement" />
                                        <div class="checkbox-content">
                                            <i class="fas fa-ellipsis-h"></i>
                                            <span>Autres</span>
                                        </div>
                                    </label>
                                </div>

                                <div v-if="form.type_logement.includes('autres')" class="mt-3">
                                    <input
                                        v-model="form.type_logement_autres"
                                        type="text"
                                        placeholder="Précisez le type de logement..."
                                        class="form-input"
                                    />
                                </div>
                            </div>

                            <!-- Nombre de pièces détaillé -->
                            <div class="form-group mt-4">
                                <label class="form-label">
                                    <i class="fas fa-th-large"></i>
                                    Configuration détaillée du logement
                                </label>
                                <div class="pieces-grid">
                                    <div class="piece-card">
                                        <i class="fas fa-bed"></i>
                                        <label>Chambres</label>
                                        <input
                                            v-model.number="form.nbchambres"
                                            type="number"
                                            min="0"
                                            class="piece-input"
                                            placeholder="0"
                                        />
                                    </div>
                                    <div class="piece-card">
                                        <i class="fas fa-couch"></i>
                                        <label>Salons</label>
                                        <input
                                            v-model.number="form.nbsalons"
                                            type="number"
                                            min="0"
                                            class="piece-input"
                                            placeholder="0"
                                        />
                                    </div>
                                    <div class="piece-card">
                                        <i class="fas fa-utensils"></i>
                                        <label>Cuisines</label>
                                        <input
                                            v-model.number="form.nbcuisines"
                                            type="number"
                                            min="0"
                                            class="piece-input"
                                            placeholder="0"
                                        />
                                    </div>
                                    <div class="piece-card">
                                        <i class="fas fa-bath"></i>
                                        <label>Salles de bains</label>
                                        <input
                                            v-model.number="form.nbsalledebains"
                                            type="number"
                                            min="0"
                                            class="piece-input"
                                            placeholder="0"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="form-grid mt-4">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-map-marker-alt"></i>
                                        Quartier souhaité
                                    </label>
                                    <input
                                        v-model="form.quartier_souhaite"
                                        type="text"
                                        class="form-input"
                                        placeholder="Ex: Mermoz, Almadies..."
                                    />
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-calculator"></i>
                                        Budget mensuel (FCFA)
                                    </label>
                                    <input
                                        v-model.number="form.budget_mensuel"
                                        type="number"
                                        min="0"
                                        class="form-input"
                                        placeholder="Ex: 150000"
                                    />
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-calendar-alt"></i>
                                        Date d'entrée souhaitée
                                    </label>
                                    <input
                                        v-model="form.date_entree_souhaitee"
                                        type="date"
                                        class="form-input"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Étape 4: Documents -->
                        <div v-show="currentStep === 3" class="form-step">
                            <div class="step-header">
                                <i class="fas fa-file-upload"></i>
                                <h3>Pièces à Fournir</h3>
                            </div>

                            <div class="documents-section">
                                <p class="documents-intro">
                                    <i class="fas fa-info-circle"></i>
                                    Cochez les documents que vous pouvez fournir
                                </p>

                                <div class="document-cards">
                                    <label class="document-card">
                                        <input type="checkbox" v-model="form.carte_identite" />
                                        <div class="document-content">
                                            <i class="fas fa-id-card"></i>
                                            <div>
                                                <h4>Carte d'identité</h4>
                                                <p>CNI ou Passeport (photocopie)</p>
                                            </div>
                                            <div class="checkmark">
                                                <i class="fas fa-check"></i>
                                            </div>
                                        </div>
                                    </label>

                                    <label class="document-card">
                                        <input type="checkbox" v-model="form.dernier_recu_loyer" />
                                        <div class="document-content">
                                            <i class="fas fa-receipt"></i>
                                            <div>
                                                <h4>Dernier reçu de loyer</h4>
                                                <p>Si vous êtes locataire actuellement</p>
                                            </div>
                                            <div class="checkmark">
                                                <i class="fas fa-check"></i>
                                            </div>
                                        </div>
                                    </label>
                                </div>

                                <div class="success-message">
                                    <i class="fas fa-check-circle"></i>
                                    <div>
                                        <h4>Dernière étape !</h4>
                                        <p>Vous êtes sur le point de soumettre votre demande. Notre équipe l'analysera et vous contactera rapidement.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </form>
                </div>

                <!-- Footer -->
                <div class="modal-footer">
                    <button
                        v-if="currentStep > 0"
                        type="button"
                        @click="previousStep"
                        class="btn-secondary"
                    >
                        <i class="fas fa-arrow-left"></i>
                        Précédent
                    </button>

                    <div class="footer-spacer"></div>

                    <button
                        type="button"
                        @click="closeModal"
                        class="btn-cancel"
                    >
                        Annuler
                    </button>

                    <button
                        v-if="currentStep < steps.length - 1"
                        type="button"
                        @click="nextStep"
                        class="btn-primary"
                    >
                        Suivant
                        <i class="fas fa-arrow-right"></i>
                    </button>

                    <button
                        v-else
                        type="button"
                        @click="submitForm"
                        :disabled="form.processing"
                        class="btn-submit"
                    >
                        <i class="fas fa-paper-plane"></i>
                        {{ form.processing ? 'Envoi en cours...' : 'Soumettre ma demande' }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(['close', 'success']);

const steps = ['Infos Personnelles', 'Infos Complémentaires', 'Logement', 'Documents'];
const currentStep = ref(0);
const showPassword = ref(false);

const form = useForm({
    name: '',
    prenom: '',
    email: '',
    telephone: '',
    password: '',
    password_confirmation: '',
    profession: '',
    numero_cni: '',
    personne_contact: '',
    telephone_contact: '',
    revenus_mensuels: null,
    nombre_personnes: null,
    situation_familiale: null,
    type_logement: [],
    type_logement_autres: '',
    nbchambres: null,
    nbsalons: null,
    nbcuisines: null,
    nbsalledebains: null,
    quartier_souhaite: '',
    budget_mensuel: null,
    date_entree_souhaitee: '',
    carte_identite: false,
    dernier_recu_loyer: false,
});

const currentDate = computed(() => {
    const today = new Date();
    return today.toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
});

const progressPercentage = computed(() => {
    return ((currentStep.value + 1) / steps.length) * 100;
});

const closeModal = () => {
    emit('close');
    currentStep.value = 0;
    form.reset();
};

const nextStep = () => {
    if (currentStep.value < steps.length - 1) {
        currentStep.value++;
    }
};

const previousStep = () => {
    if (currentStep.value > 0) {
        currentStep.value--;
    }
};

const submitForm = () => {
    form.post(route('client-dossiers.store'), {
        onSuccess: () => {
            emit('success');
            closeModal();
        },
        onError: () => {
            // Les erreurs sont automatiquement gérées par Inertia dans form.errors
        },
    });
};
</script>

<style scoped>
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.75);
    backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 20px;
    overflow-y: auto;
}

.modal-container {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: 24px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    max-width: 900px;
    width: 100%;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    animation: slideUp 0.4s ease;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-header {
    padding: 30px;
    border-bottom: 2px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%);
    color: white;
    border-radius: 24px 24px 0 0;
}

.header-content {
    display: flex;
    align-items: center;
    gap: 20px;
}

.header-icon {
    width: 60px;
    height: 60px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
}

.modal-title {
    font-size: 28px;
    font-weight: 700;
    margin: 0;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
}

.modal-subtitle {
    font-size: 14px;
    opacity: 0.9;
    margin: 5px 0 0 0;
}

.close-button {
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.2);
    border: none;
    border-radius: 12px;
    color: white;
    font-size: 20px;
    cursor: pointer;
    transition: all 0.3s;
}

.close-button:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
}

.progress-indicator {
    padding: 25px 30px;
    background: white;
    border-bottom: 1px solid #e5e7eb;
}

.progress-bar {
    height: 8px;
    background: #e5e7eb;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 20px;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    transition: width 0.4s ease;
    border-radius: 10px;
}

.progress-steps {
    display: flex;
    justify-content: space-between;
    gap: 10px;
}

.progress-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    flex: 1;
}

.step-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e5e7eb;
    color: #6b7280;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    transition: all 0.3s;
}

.progress-step.active .step-circle {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.progress-step.completed .step-circle {
    background: #10b981;
    color: white;
}

.step-label {
    font-size: 12px;
    font-weight: 500;
    color: #6b7280;
    text-align: center;
}

.progress-step.active .step-label {
    color: #667eea;
    font-weight: 600;
}

.modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 30px;
}

.form-step {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateX(20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.step-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e5e7eb;
}

.step-header i {
    font-size: 28px;
    color: #667eea;
}

.step-header h3 {
    font-size: 22px;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: 600;
    color: #374151;
}

.form-label i {
    color: #667eea;
    font-size: 16px;
}

.required {
    color: #ef4444;
}

.form-input {
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 15px;
    transition: all 0.3s;
    background: white;
}

.form-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.input-error {
    border-color: #ef4444 !important;
}

.password-input {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #6b7280;
    cursor: pointer;
    font-size: 18px;
    transition: color 0.3s;
}

.password-toggle:hover {
    color: #667eea;
}

.input-hint {
    font-size: 12px;
    color: #6b7280;
    margin: 0;
}

.error-message {
    font-size: 12px;
    color: #ef4444;
    margin-top: 4px;
}

.mt-3 {
    margin-top: 12px;
}

.mt-4 {
    margin-top: 16px;
}

.radio-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 12px;
}

.radio-grid-2 {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}

.radio-card {
    position: relative;
    cursor: pointer;
}

.radio-card input[type="radio"] {
    position: absolute;
    opacity: 0;
}

.radio-content {
    padding: 16px;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    text-align: center;
    transition: all 0.3s;
    background: white;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.radio-content i {
    font-size: 24px;
    color: #667eea;
}

.radio-content span {
    font-size: 13px;
    font-weight: 500;
    color: #374151;
}

.radio-card input:checked + .radio-content {
    border-color: #667eea;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
}

.checkbox-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 12px;
}

.checkbox-card {
    position: relative;
    cursor: pointer;
}

.checkbox-card input[type="checkbox"] {
    position: absolute;
    opacity: 0;
}

.checkbox-content {
    padding: 16px;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    text-align: center;
    transition: all 0.3s;
    background: white;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    height: 100%;
}

.checkbox-content i {
    font-size: 28px;
    color: #667eea;
}

.checkbox-content span {
    font-size: 13px;
    font-weight: 500;
    color: #374151;
}

.checkbox-card input:checked + .checkbox-content {
    border-color: #667eea;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
}

.documents-section {
    max-width: 600px;
    margin: 0 auto;
}

.documents-intro {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    padding: 16px 20px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 24px;
    color: #1e40af;
    font-weight: 500;
}

.documents-intro i {
    font-size: 20px;
}

.document-cards {
    display: flex;
    flex-direction: column;
    gap: 16px;
    margin-bottom: 24px;
}

.document-card {
    cursor: pointer;
    display: block;
}

.document-card input[type="checkbox"] {
    display: none;
}

.document-content {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;
    border: 2px solid #e5e7eb;
    border-radius: 16px;
    background: white;
    transition: all 0.3s;
}

.document-content > i {
    font-size: 32px;
    color: #667eea;
    width: 50px;
    text-align: center;
}

.document-content > div:nth-child(2) {
    flex: 1;
}

.document-content h4 {
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 4px 0;
}

.document-content p {
    font-size: 13px;
    color: #6b7280;
    margin: 0;
}

.checkmark {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
    flex-shrink: 0;
}

.checkmark i {
    font-size: 16px;
    color: white;
    opacity: 0;
    transition: opacity 0.3s;
}

.document-card input:checked + .document-content {
    border-color: #10b981;
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, rgba(5, 150, 105, 0.05) 100%);
}

.document-card input:checked + .document-content .checkmark {
    background: #10b981;
}

.document-card input:checked + .document-content .checkmark i {
    opacity: 1;
}

.document-card:hover .document-content {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.success-message {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    padding: 20px;
    border-radius: 16px;
    display: flex;
    align-items: flex-start;
    gap: 16px;
    border: 2px solid #10b981;
}

.success-message > i {
    font-size: 28px;
    color: #059669;
    flex-shrink: 0;
}

.success-message h4 {
    font-size: 18px;
    font-weight: 700;
    color: #065f46;
    margin: 0 0 8px 0;
}

.success-message p {
    font-size: 14px;
    color: #047857;
    margin: 0;
    line-height: 1.6;
}

.modal-footer {
    padding: 20px 30px;
    border-top: 2px solid #e5e7eb;
    display: flex;
    align-items: center;
    gap: 12px;
    background: white;
    border-radius: 0 0 24px 24px;
}

.footer-spacer {
    flex: 1;
}

.btn-secondary,
.btn-cancel,
.btn-primary,
.btn-submit {
    padding: 12px 24px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 15px;
    cursor: pointer;
    transition: all 0.3s;
    border: none;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-secondary {
    background: #f3f4f6;
    color: #374151;
}

.btn-secondary:hover {
    background: #e5e7eb;
    transform: translateX(-2px);
}

.btn-cancel {
    background: #fee2e2;
    color: #dc2626;
}

.btn-cancel:hover {
    background: #fecaca;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover {
    transform: translateX(2px);
    box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
}

.btn-submit {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-submit:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
}

.btn-submit:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-active .modal-container,
.modal-leave-active .modal-container {
    transition: transform 0.3s ease;
}

.modal-enter-from .modal-container,
.modal-leave-to .modal-container {
    transform: scale(0.9);
}

@media (max-width: 768px) {
    .modal-container {
        max-height: 95vh;
        border-radius: 16px;
    }

    .modal-header {
        padding: 20px;
        border-radius: 16px 16px 0 0;
    }

    .header-icon {
        width: 50px;
        height: 50px;
        font-size: 24px;
    }

    .modal-title {
        font-size: 20px;
    }

    .modal-body {
        padding: 20px;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .progress-steps {
        overflow-x: auto;
        padding-bottom: 10px;
    }

    .step-label {
        font-size: 10px;
    }

    .radio-grid,
    .checkbox-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .modal-footer {
        flex-wrap: wrap;
    }

    .footer-spacer {
        display: none;
    }
}

.modal-body::-webkit-scrollbar {
    width: 8px;
}

.modal-body::-webkit-scrollbar-track {
    background: #f3f4f6;
    border-radius: 10px;
}

.modal-body::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
}

.modal-body::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
}

/* Styles pour les pièces */
.pieces-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 16px;
}

.piece-card {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 16px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}

.piece-card:hover {
    border-color: #667eea;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
}

.piece-card > i {
    font-size: 32px;
    color: #667eea;
}

.piece-card > label {
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    margin: 0;
}

.piece-input {
    width: 100%;
    padding: 10px;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    text-align: center;
    font-size: 18px;
    font-weight: 600;
    color: #1f2937;
    transition: all 0.3s;
}

.piece-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.piece-input::placeholder {
    color: #9ca3af;
    font-weight: 400;
}

</style>
