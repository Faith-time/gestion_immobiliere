<!-- Dans le template des réservations, mettre à jour cette partie -->

<div class="btn-group" role="group">
<!-- Bouton paiement si validée -->
<Link v-if="reservation.statut === 'validee'"
      :href="route('reservations.initier-paiement', reservation.id)"
      class="btn btn-sm btn-success">
    <i class="fas fa-credit-card me-1"></i>Payer
</Link>

<!-- Bouton édition si en attente -->
<Link v-if="reservation.statut === 'en_attente'"
      :href="route('reservations.edit', reservation.id)"
      class="btn btn-sm btn-outline-secondary">
    <i class="fas fa-edit me-1"></i>Modifier
</Link>

<!-- Bouton annulation -->
<button v-if="['en_attente', 'validee'].includes(reservation.statut)"
        @click="annulerReservation(reservation.id)"
        class="btn btn-sm btn-outline-danger">
    <i class="fas fa-times me-1"></i>Annuler
</button>
</div>
