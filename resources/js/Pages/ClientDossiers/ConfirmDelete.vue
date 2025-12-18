<template>
    <Transition name="modal">
        <div v-if="show" class="modal-overlay" @click.self="$emit('close')">
            <div class="modal-container">
                <div class="modal-header">
                    <div class="warning-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h2 class="modal-title">Confirmer la suppression</h2>
                </div>

                <div class="modal-body">
                    <p class="warning-text">
                        Êtes-vous sûr de vouloir supprimer votre dossier client ?
                    </p>
                    <p class="warning-subtext">
                        Cette action est <strong>irréversible</strong> et entraînera la suppression de :
                    </p>
                    <ul class="warning-list">
                        <li><i class="fas fa-times-circle text-danger"></i> Toutes vos informations personnelles</li>
                        <li><i class="fas fa-times-circle text-danger"></i> Vos critères de recherche</li>
                        <li><i class="fas fa-times-circle text-danger"></i> Les documents joints (CNI, etc.)</li>
                        <li><i class="fas fa-times-circle text-danger"></i> L'historique de votre dossier</li>
                    </ul>
                    <div class="alert alert-danger mt-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note :</strong> Vous pourrez créer un nouveau dossier à tout moment.
                    </div>
                </div>

                <div class="modal-footer">
                    <button @click="$emit('close')" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                    <button @click="$emit('confirm')" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Supprimer définitivement
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup>
defineProps({
    show: Boolean
})

defineEmits(['close', 'confirm'])
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
}

.modal-container {
    background: white;
    border-radius: 20px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    max-width: 500px;
    width: 100%;
    animation: slideUp 0.3s ease;
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
    padding: 2rem;
    border-bottom: 2px solid #fee2e2;
    text-align: center;
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    border-radius: 20px 20px 0 0;
}

.warning-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 1rem;
    background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

.warning-icon i {
    font-size: 2.5rem;
    color: white;
}

.modal-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
    color: #991b1b;
}

.modal-body {
    padding: 2rem;
}

.warning-text {
    font-size: 1.1rem;
    font-weight: 600;
    color: #212529;
    margin-bottom: 1rem;
}

.warning-subtext {
    font-size: 0.95rem;
    color: #6c757d;
    margin-bottom: 1rem;
}

.warning-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.warning-list li {
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    background: #fff5f5;
    border-left: 3px solid #dc2626;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-weight: 500;
}

.warning-list li i {
    font-size: 1.1rem;
}

.modal-footer {
    padding: 1.5rem 2rem;
    border-top: 2px solid #e5e7eb;
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    background: #f8f9fa;
    border-radius: 0 0 20px 20px;
}

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 10px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-2px);
}

.btn-danger {
    background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
    color: white;
}

.btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4);
}

.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
