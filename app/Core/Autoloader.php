<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Autoloader PSR-4 bez Composera.
 *
 * Mapuje prefiksy przestrzeni nazw na katalogi w systemie plików.
 * Obsługuje wiele prefiksów i wiele katalogów bazowych na prefiks,
 * zgodnie ze specyfikacją PSR-4.
 *
 * Użycie:
 *   $loader = new Autoloader();
 *   $loader->addPrefix('App\\', BASE_PATH . '/app');
 *   $loader->register();
 *
 * @version 1.0.0
 */
class Autoloader
{
    /**
     * Tablica prefiksów namespace → lista katalogów bazowych.
     * Jeden prefiks może mieć wiele katalogów (np. moduły).
     *
     * @var array<string, list<string>>
     */
    private array $prefixes = [];

    /**
     * Rejestruje prefiks namespace i odpowiadający mu katalog bazowy.
     *
     * Konwencja PSR-4: 'App\\Controllers\\AuthController'
     *   przy prefix='App\\' i baseDir='/var/www/app'
     *   zostanie załadowany z: /var/www/app/Controllers/AuthController.php
     *
     * Metodę można wywołać wielokrotnie dla tego samego prefiksu —
     * każdy kolejny katalog jest dołączany na końcu listy (fallback).
     *
     * @param string $prefix  Prefiks namespace, np. 'App\\'
     * @param string $baseDir Ścieżka bezwzględna do katalogu bazowego
     * @param bool   $prepend Gdy true — katalog jest dodawany na POCZĄTKU listy
     */
    public function addPrefix(string $prefix, string $baseDir, bool $prepend = false): void
    {
        // Normalizujemy prefiks — zawsze kończy się separatorem namespace
        $prefix = trim($prefix, '\\') . '\\';

        // Normalizujemy ścieżkę — zawsze kończy się separatorem katalogów
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        // Inicjalizujemy tablicę dla tego prefiksu, jeśli nie istnieje
        if (!isset($this->prefixes[$prefix])) {
            $this->prefixes[$prefix] = [];
        }

        // Dodajemy katalog na początku lub końcu listy
        if ($prepend) {
            array_unshift($this->prefixes[$prefix], $baseDir);
        } else {
            $this->prefixes[$prefix][] = $baseDir;
        }
    }

    /**
     * Rejestruje autoloader w stosie spl_autoload_register().
     *
     * @param bool $prepend Gdy true — autoloader jest dodawany na początku stosu
     */
    public function register(bool $prepend = false): void
    {
        spl_autoload_register([$this, 'loadClass'], true, $prepend);
    }

    /**
     * Wyrejestrowuje autoloader ze stosu spl_autoload.
     * Przydatne w testach lub przy dynamicznej podmianie loadera.
     */
    public function unregister(): void
    {
        spl_autoload_unregister([$this, 'loadClass']);
    }

    /**
     * Ładuje plik klasy odpowiadający podanej nazwie FQCN.
     *
     * Wywoływane automatycznie przez PHP przy pierwszym użyciu klasy.
     *
     * @param  string      $class Pełna nazwa klasy (FQCN), np. 'App\Controllers\AuthController'
     * @return string|false       Ścieżka załadowanego pliku lub false gdy nie znaleziono
     */
    public function loadClass(string $class): string|false
    {
        // Przechodzimy przez zarejestrowane prefiksy od najdłuższego
        // (bardziej szczegółowe prefiksy mają pierwszeństwo)
        foreach ($this->prefixes as $prefix => $baseDirs) {

            // Sprawdzamy czy klasa należy do tego prefiksu
            if (!str_starts_with($class, $prefix)) {
                continue;
            }

            // Wycinamy część nazwy klasy po prefiksie (relatywna część)
            $relativeClass = substr($class, strlen($prefix));

            // Próbujemy każdy katalog bazowy przypisany do prefiksu
            foreach ($baseDirs as $baseDir) {
                $file = $this->resolveFile($baseDir, $relativeClass);

                if ($file !== false) {
                    return $file; // Plik znaleziony i załadowany
                }
            }
        }

        // Klasy nie obsługujemy — przekazujemy do kolejnego loadera w stosie
        return false;
    }

    /**
     * Buduje i weryfikuje ścieżkę do pliku klasy.
     *
     * Zamienia separatory namespace (\) na separatory katalogów
     * i dodaje rozszerzenie .php zgodnie z PSR-4.
     *
     * @param  string      $baseDir       Katalog bazowy (ze slash na końcu)
     * @param  string      $relativeClass Relatywna część nazwy klasy, np. 'Controllers\AuthController'
     * @return string|false               Ścieżka do pliku lub false, gdy plik nie istnieje
     */
    private function resolveFile(string $baseDir, string $relativeClass): string|false
    {
        // Zamieniamy separatory namespace na separatory systemu plików + .php
        $file = $baseDir
            . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass)
            . '.php';

        if (is_file($file)) {
            require $file; // Ładujemy plik klasy
            return $file;
        }

        return false;
    }

    /**
     * Zwraca aktualnie zarejestrowane prefiksy (pomocne przy debugowaniu).
     *
     * @return array<string, list<string>>
     */
    public function getPrefixes(): array
    {
        return $this->prefixes;
    }
}

/*
 * ============================================================
 * DOKUMENTACJA PLIKU: Autoloader.php
 * ============================================================
 * Plik:         app/Core/Autoloader.php
 * Opis:         Autoloader PSR-4 bez Composera.
 *               Mapuje prefiksy namespace na katalogi,
 *               ładuje pliki klas zgodnie z konwencją PSR-4.
 * Wersja:       1.0.0
 * Zależności:   Brak (czysty PHP 8.0+)
 * Uwagi:        Wymaga PHP 8.0+ (str_starts_with, union type string|false).
 *               Dla PHP 7.4 zastąp str_starts_with() przez strncmp() === 0
 *               oraz typ zwracany zmień na: bool|string
 * ============================================================
 */
