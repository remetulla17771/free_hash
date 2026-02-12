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
                    — <?= htmlspecialchars($e) ?>
                </small>
            </h4>
        </div>

        <div class="card-body">

            <div class="alert alert-danger">
                <strong><?= htmlspecialchars($message) ?></strong>
            </div>


            <a href="/" class="btn btn-outline-danger mt-4">
                ← На главную
            </a>

        </div>
    </div>
</div>
