<?php
/**
 * Structured Philippine address fields.
 *
 * @var string $prefix     POST name prefix ('' or 'delivery_')
 * @var array  $values     province, city, barangay, street
 * @var array  $errors     field => message
 * @var bool   $showPhone  include phone field
 * @var string $phoneValue
 * @var string $phoneErrorKey
 */
$prefix        = $prefix ?? '';
$values        = $values ?? ['province' => '', 'city' => '', 'barangay' => '', 'street' => ''];
$errors        = $errors ?? [];
$showPhone     = $showPhone ?? false;
$phoneValue    = $phoneValue ?? '';
$phoneErrorKey = $phoneErrorKey ?? ($prefix === 'delivery_' ? 'delivery_phone' : 'phone');
$phoneId       = $prefix === 'delivery_' ? 'delivery_phone' : 'phone';
$phoneName     = $prefix === 'delivery_' ? 'delivery_phone' : 'phone';
$phoneLabel    = $phoneLabel ?? ($prefix === 'delivery_' ? 'Delivery phone' : 'Phone');

$name = static function (string $field) use ($prefix): string {
    return $prefix . $field;
};
?>
<?php if ($showPhone): ?>
<div class="col-12">
    <label for="<?= htmlspecialchars($phoneId, ENT_QUOTES, 'UTF-8') ?>" class="form-label"><?= htmlspecialchars($phoneLabel, ENT_QUOTES, 'UTF-8') ?></label>
    <input type="tel"
           class="form-control <?= !empty($errors[$phoneErrorKey]) ? 'is-invalid' : '' ?>"
           id="<?= htmlspecialchars($phoneId, ENT_QUOTES, 'UTF-8') ?>"
           name="<?= htmlspecialchars($phoneName, ENT_QUOTES, 'UTF-8') ?>"
           value="<?= htmlspecialchars($phoneValue, ENT_QUOTES, 'UTF-8') ?>"
           placeholder="09XX XXX XXXX"
           required>
    <?php if (!empty($errors[$phoneErrorKey])): ?>
        <div class="invalid-feedback"><?= htmlspecialchars($errors[$phoneErrorKey], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="col-12">
    <div class="form-label mb-2">Address</div>
</div>

<div class="col-12 col-md-6">
    <label for="<?= $name('province') ?>" class="form-label">Province</label>
    <input type="text"
           class="form-control <?= !empty($errors['province']) ? 'is-invalid' : '' ?>"
           id="<?= $name('province') ?>"
           name="<?= $name('province') ?>"
           value="<?= htmlspecialchars($values['province'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
           placeholder="e.g. Metro Manila"
           required>
    <?php if (!empty($errors['province'])): ?>
        <div class="invalid-feedback"><?= htmlspecialchars($errors['province'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
</div>

<div class="col-12 col-md-6">
    <label for="<?= $name('city') ?>" class="form-label">City / Municipality</label>
    <input type="text"
           class="form-control <?= !empty($errors['city']) ? 'is-invalid' : '' ?>"
           id="<?= $name('city') ?>"
           name="<?= $name('city') ?>"
           value="<?= htmlspecialchars($values['city'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
           placeholder="e.g. Quezon City"
           required>
    <?php if (!empty($errors['city'])): ?>
        <div class="invalid-feedback"><?= htmlspecialchars($errors['city'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
</div>

<div class="col-12 col-md-6">
    <label for="<?= $name('barangay') ?>" class="form-label">Barangay</label>
    <input type="text"
           class="form-control <?= !empty($errors['barangay']) ? 'is-invalid' : '' ?>"
           id="<?= $name('barangay') ?>"
           name="<?= $name('barangay') ?>"
           value="<?= htmlspecialchars($values['barangay'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
           placeholder="e.g. Brgy. San Roque"
           required>
    <?php if (!empty($errors['barangay'])): ?>
        <div class="invalid-feedback"><?= htmlspecialchars($errors['barangay'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
</div>

<div class="col-12 col-md-6">
    <label for="<?= $name('street') ?>" class="form-label">Street / Purok</label>
    <input type="text"
           class="form-control <?= !empty($errors['street']) ? 'is-invalid' : '' ?>"
           id="<?= $name('street') ?>"
           name="<?= $name('street') ?>"
           value="<?= htmlspecialchars($values['street'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
           placeholder="e.g. 123 Main St., Purok 5"
           required>
    <?php if (!empty($errors['street'])): ?>
        <div class="invalid-feedback"><?= htmlspecialchars($errors['street'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
</div>
