<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-10">
    <div class="d-flex align-items-center gap-3">
        <?php if (isset($pageLink) && isset($pageName)) { ?>
            <a href="<?= $pageLink ?>" class="btn btn-secondary btn-sm d-flex align-items-center gap-1">
                <iconify-icon icon="lucide:arrow-left"></iconify-icon> Back
            </a>
        <?php } ?>
        <h6 class="fw-semibold mb-0"><?= ($title) ?></h6>
    </div>
    <div class="d-flex align-items-center gap-3">
        <?php if (isset($addButtonLink) && isset($addButtonText)) { ?>
            <a href="<?= $addButtonLink ?>" class="btn btn-primary btn-sm"><?= $addButtonText ?></a>
        <?php } ?>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="dashboard.php" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <?php if (isset($subTitle)) { ?>
                <li>/</li>
                <li class="fw-medium"><?= ($subTitle) ?></li>
            <?php } ?>
            <?php if (isset($pageLink) && isset($pageName)) { ?>
                <li>/</li>
                <li class="fw-medium"><a href="<?= $pageLink ?>" class="text-primary"><?= ($pageName) ?></a></li>
            <?php } ?>
        </ul>
    </div>
</div>
<?php include 'msg-template.php'; ?>