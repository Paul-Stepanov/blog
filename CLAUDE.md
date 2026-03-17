# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Это PHP проект блога. Проект находится на начальной стадии разработки.

## Technology Stack

- **Language**: PHP 8.3
- **IDE**: PhpStorm с настроенными инструментами:
  - PHPStan (статический анализ)
  - Psalm (статический анализ)
  - PHP CodeSniffer (стиль кода)
  - PHP CS Fixer (автоматическое исправление стиля)
  - PHPMD (PHP Mess Detector)

## Coding Standards

Проект следует глобальным стандартам кодирования из `~/.claude/CLAUDE.md`:
- PSR-1 и PSR-12 для стиля кода
- `declare(strict_types=1);` в начале каждого файла
- Типизированные аргументы и возвращаемые значения
- PHPDoc для публичных методов и классов
- Принципы DRY, KISS, YAGNI

## Project Structure

Структура проекта будет определена по мере разработки.