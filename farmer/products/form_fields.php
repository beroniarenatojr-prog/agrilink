<?php
/**
 * Shared form fields for create and edit product forms.
 * Expects: $categories, $unitTypes, and optionally $product (for edit).
 */
$p = $product ?? [];
?>
<div class="row g-3">
    <div class="col-12">
        <label for="name" class="form-label">Product name</label>
        <input type="text" class="form-control" id="name" name="name"
               value="<?= htmlspecialchars($p['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
    </div>

    <div class="col-12 col-md-6">
        <label for="category_id" class="form-label">Category</label>
        <select class="form-select" id="category_id" name="category_id">
            <option value="">— None —</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"
                    <?= ((int)($p['category_id'] ?? 0) === (int)$cat['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
        <div id="priceHint" class="price-hint-box" style="display:none"></div>
    </div>

    <div class="col-12 col-md-6">
        <label for="unit_type" class="form-label">Unit type</label>
        <select class="form-select" id="unit_type" name="unit_type" required>
            <?php foreach ($unitTypes as $u): ?>
                <option value="<?= $u ?>" <?= ($p['unit_type'] ?? 'kg') === $u ? 'selected' : '' ?>><?= $u ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-12 col-md-6">
        <label for="price_per_unit" class="form-label">Price per unit (₱)</label>
        <input type="number" class="form-control" id="price_per_unit" name="price_per_unit"
               value="<?= htmlspecialchars((string)($p['price_per_unit'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
               min="0.01" step="0.01" required>
    </div>

    <div class="col-12 col-md-6">
        <label for="stock_quantity" class="form-label">Stock quantity</label>
        <input type="number" class="form-control" id="stock_quantity" name="stock_quantity"
               value="<?= htmlspecialchars((string)($p['stock_quantity'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
               min="0" step="1" required>
    </div>

    <div class="col-12">
        <label for="description" class="form-label">Description <span class="text-muted fw-normal">(optional)</span></label>
        <textarea class="form-control" id="description" name="description" rows="4"><?= htmlspecialchars($p['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
    </div>

    <!-- Main image -->
    <div class="col-12">
        <label for="image" class="form-label">
            Main image
            <?php if (!empty($p['image'])): ?>
                <span class="text-muted fw-normal">(leave empty to keep current)</span>
            <?php endif; ?>
        </label>
        <?php if (!empty($p['image'])): ?>
            <div class="mb-2">
                <img src="<?= url('/media.php?path=' . urlencode($p['image'])) ?>"
                     style="width:80px;height:80px;object-fit:cover;border-radius:8px;border:1px solid var(--agri-border)" alt="Current">
            </div>
        <?php endif; ?>
        <input type="file" class="form-control" id="image" name="image" accept="image/jpeg,image/png,image/webp">
        <div class="form-text">JPEG, PNG or WebP · max 2 MB</div>
    </div>

    <!-- Additional images -->
    <div class="col-12">
        <label class="form-label">
            Additional images
            <span class="text-muted fw-normal">(up to 3, max 4 total)</span>
        </label>
        <?php
        $additionalImages = is_string($p['additional_images'] ?? null)
            ? json_decode($p['additional_images'], true)
            : ($p['additional_images'] ?? []);
        ?>
        <?php if (!empty($additionalImages)): ?>
            <div class="d-flex gap-2 mb-2 flex-wrap">
                <?php foreach ($additionalImages as $ai): ?>
                    <img src="<?= url('/media.php?path=' . urlencode($ai)) ?>"
                         style="width:64px;height:64px;object-fit:cover;border-radius:8px;border:1px solid var(--agri-border)" alt="">
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <input type="file" class="form-control" name="additional_images[]" accept="image/jpeg,image/png,image/webp" multiple>
        <div class="form-text">Upload up to 3 additional photos. Replaces current additional images if files are selected.</div>
    </div>

    <div class="col-12">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="is_available" name="is_available" value="1"
                   <?= ($p['is_available'] ?? 1) ? 'checked' : '' ?>>
            <label class="form-check-label" for="is_available">Listed as available</label>
        </div>
    </div>

    <div class="col-12 mt-1">
        <button type="submit" class="btn btn-agri">Save Product</button>
    </div>
</div>
