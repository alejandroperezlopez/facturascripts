<?php

/*
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/// comprobamos que se haya instalado y haya configuración
if (!file_exists(__DIR__ . '/config.php')) {
    die("ERROR - NO_CONFIG -");
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

/// iniciamos la aplicación
$app = new FacturaScripts\Core\Base\App(__DIR__);

/// conectamos a la base de datos, cache, etc
$app->connect();

/// ejecutamos el cron
$app->runCron();

/// desconectamos de todo
$app->close();