<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Prosty DI Container oparty na tablicy fabryk (Closures).
 *
 * Każda zależność jest rejestrowana jako anonimowa funkcja (factory),
 * która jest wywoływana raz — wynik jest cachowany (singleton).
 * Nie wymaga Composera ani żadnych zewnętrznych bibliotek.
 */
class Container
{
    /** @var array<string, \Closure> Zarejestrowane fabryki */
    private array $bindings = [];

    /** @var array<string, mixed> Cache zbudowanych instancji (singleton) */
    private array $instances = [];

    /**
     * Rejestruje fabrykę dla danego identyfikatora.
     *
     * @param string   $abstract Identyfikator zależności (np. pełna nazwa klasy)
     * @param \Closure $factory  Funkcja tworząca instancję; otrzymuje Container jako argument
     */
    public function bind(string $abstract, \Closure $factory): void
    {
        // Nadpisanie istniejącego bindingu usuwa też cache
        $this->bindings[$abstract] = $factory;
        unset($this->instances[$abstract]);
    }

    /**
     * Pobiera instancję dla danego identyfikatora.
     * Pierwsze wywołanie buduje instancję, kolejne zwracają cache (singleton).
     *
     * @param string $abstract Identyfikator zależności
     * @return mixed           Gotowa instancja
     * @throws \RuntimeException Gdy identyfikator nie jest zarejestrowany
     */
    public function get(string $abstract): mixed
    {
        // Zwróć wcześniej zbudowaną instancję (singleton)
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (!$this->has($abstract)) {
            throw new \RuntimeException(
                "Container: brak zarejestrowanej fabryki dla [{$abstract}]."
            );
        }

        // Wywołaj fabrykę i zapisz wynik w cache
        $this->instances[$abstract] = ($this->bindings[$abstract])($this);

        return $this->instances[$abstract];
    }

    /**
     * Sprawdza, czy identyfikator jest zarejestrowany w kontenerze.
     *
     * @param string $abstract Identyfikator zależności
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]);
    }
}

/*
 * ============================================================
 * DOKUMENTACJA PLIKU: Container.php
 * ============================================================
 * Plik:        app/Core/Container.php
 * Opis:        Prosty DI Container (tablica fabryk, singleton cache)
 * Zależności:  brak (czyste PHP)
 * Uwagi:       PHP 7.4+ (mixed wymaga PHP 8.0 — zamień na komentarz
 *              jeśli używasz PHP 7.4, patrz niżej)
 * ============================================================
 *
 * PHP 7.4 — usuń typowanie "mixed" z sygnatury get():
 *   public function get(string $abstract)
 */
