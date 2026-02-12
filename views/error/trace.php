<?php
/** @var Throwable $e */
/** @var int $code */

?>

<div class="container my-5">
    <div class="card shadow-lg border-0">

        <div class="card-header bg-danger text-white">
            <h4 class="mb-0">
                Error <?= $code ?>
                <small class="fw-normal opacity-75">
                    — <?= htmlspecialchars(get_class($e)) ?>
                </small>
            </h4>
        </div>

        <div class="card-body">

            <div class="alert alert-danger">
                <strong><?= htmlspecialchars($e->getMessage()) ?></strong>
            </div>

            <p class="text-muted mb-3">
                <?= $e->getFile() ?> :
                <span class="fw-bold"><?= $e->getLine() ?></span>
            </p>

            <h6 class="text-uppercase text-muted mb-2">Stack trace</h6>

            <div class="bg-dark text-light rounded p-3"
                 style="font-family: Consolas, monospace; font-size: 13px; max-height: 400px; overflow:auto">

                <?php foreach ($e->getTrace() as $i => $t): ?>
                    <div class="border-bottom border-secondary pb-2 mb-2">
                        <span class="text-warning">#<?= $i ?></span>

                        <span class="text-info">
                            <?= $t['file'] ?? '[internal]' ?>
                        </span>

                        <?php if (isset($t['line'])): ?>
                            <span class="text-success">
                                Line:<?= $t['line'] ?>
                            </span>
                        <?php endif; ?>
                            <span>-></span>
                        <span class="text-light-emphasis">
                            <?= ($t['class'] ?? '') . ($t['type'] ?? '') . ($t['function'] ?? '') ?>()
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>

            <a href="/" class="btn btn-outline-danger mt-4">
                ← На главную
            </a>

        </div>
    </div>
</div>
