<?php

declare(strict_types=1);

/**
 * bindings.php — Rejestracja wszystkich zależności w kontenerze DI.
 *
 * Plik includowany raz w index.php po inicjalizacji autoloadera.
 * Zmienna $container musi być dostępna w tym scope (przekazana przez require).
 *
 * Kolejność rejestracji ma znaczenie tylko wtedy, gdy jedna fabryka
 * jawnie wywołuje $c->get() dla innej — wtedy zależność musi być
 * zarejestrowana wcześniej ALBO po prostu zarejestrowana (lazy eval).
 *
 * @var \App\Core\Container $container
 */

use App\Helpers\Database;
use App\Models\FailureModel;
use App\Models\StatusModel;
use App\Models\CategoryModel;
use App\Models\DictionaryModel;
use App\Models\ProductionLineModel;
use App\Models\AssignmentModel;
use App\Models\UserModel;
use App\Models\SettingsModel;
use App\Models\SparePartModel;
use App\Models\SparePartCategoryModel;
use App\Models\SymptomModel;
use App\Models\MaintenanceModel;
use App\Models\ScheduleNoteModel;
use App\Repositories\PdoFailureRepository;
use App\Services\FailureService;
use App\Controllers\FailureController;

// ── 1. Infrastruktura ─────────────────────────────────────────────────────────

// PDO — singleton przez istniejący helper Database::get()
$container->bind(\PDO::class, fn(\App\Core\Container $c): \PDO =>
    Database::get()
);

// ── 2. Modele (istniejące klasy — zachowujemy kompatybilność wsteczną) ────────

$container->bind(FailureModel::class, fn(\App\Core\Container $c): FailureModel =>
    new FailureModel()
);

$container->bind(StatusModel::class, fn(\App\Core\Container $c): StatusModel =>
    new StatusModel()
);

$container->bind(CategoryModel::class, fn(\App\Core\Container $c): CategoryModel =>
    new CategoryModel()
);

$container->bind(DictionaryModel::class, fn(\App\Core\Container $c): DictionaryModel =>
    new DictionaryModel()
);

$container->bind(ProductionLineModel::class, fn(\App\Core\Container $c): ProductionLineModel =>
    new ProductionLineModel()
);

$container->bind(AssignmentModel::class, fn(\App\Core\Container $c): AssignmentModel =>
    new AssignmentModel()
);

$container->bind(UserModel::class, fn(\App\Core\Container $c): UserModel =>
    new UserModel()
);

$container->bind(SettingsModel::class, fn(\App\Core\Container $c): SettingsModel =>
    new SettingsModel()
);

$container->bind(SparePartModel::class, fn(\App\Core\Container $c): SparePartModel =>
    new SparePartModel()
);

$container->bind(SparePartCategoryModel::class, fn(\App\Core\Container $c): SparePartCategoryModel =>
    new SparePartCategoryModel()
);

$container->bind(SymptomModel::class, fn(\App\Core\Container $c): SymptomModel =>
    new SymptomModel()
);

$container->bind(MaintenanceModel::class, fn(\App\Core\Container $c): MaintenanceModel =>
    new MaintenanceModel()
);

$container->bind(ScheduleNoteModel::class, fn(\App\Core\Container $c): ScheduleNoteModel =>
    new ScheduleNoteModel()
);

// ── 3. Repositories ───────────────────────────────────────────────────────────

$container->bind(PdoFailureRepository::class, fn(\App\Core\Container $c): PdoFailureRepository =>
    // Repository pobiera PDO bezpośrednio — nie przez FailureModel
    new PdoFailureRepository($c->get(\PDO::class))
);

// ── 4. Services ───────────────────────────────────────────────────────────────

$container->bind(FailureService::class, fn(\App\Core\Container $c): FailureService =>
    new FailureService(
        $c->get(PdoFailureRepository::class),
        $c->get(StatusModel::class),
        $c->get(AssignmentModel::class),
        $c->get(SettingsModel::class)
    )
);

// ── 5. Controllers ────────────────────────────────────────────────────────────

$container->bind(FailureController::class, fn(\App\Core\Container $c): FailureController =>
    new FailureController(
        $c->get(FailureService::class),
        $c->get(CategoryModel::class),
        $c->get(DictionaryModel::class),
        $c->get(ProductionLineModel::class),
        $c->get(StatusModel::class),
        $c->get(UserModel::class),
        $c->get(SparePartModel::class),
        $c->get(SparePartCategoryModel::class),
        $c->get(SymptomModel::class),
        $c->get(AssignmentModel::class),
        $c->get(MaintenanceModel::class),
        $c->get(ScheduleNoteModel::class),
        $c->get(SettingsModel::class)
    )
);

/*
 * ============================================================
 * DOKUMENTACJA PLIKU: bindings.php
 * ============================================================
 * Plik:        config/bindings.php
 * Opis:        Rejestracja fabryk w DI Container dla całej aplikacji
 * Zależności:  Container, wszystkie klasy w namespace App\*
 * Uwagi:       Includowany w index.php po autoloaderze.
 *              Zmienna $container musi istnieć przed include.
 * ============================================================
 */
