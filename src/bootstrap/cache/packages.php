<?php

return  [
    'darkaonline/l5-swagger'
     => [
         'aliases'
          => [
              'L5Swagger' => 'L5Swagger\\L5SwaggerFacade',
          ],
         'providers'
          => [
              0 => 'L5Swagger\\L5SwaggerServiceProvider',
          ],
     ],
    'laravel/boost'
     => [
         'providers'
          => [
              0 => 'Laravel\\Boost\\BoostServiceProvider',
          ],
     ],
    'laravel/mcp'
     => [
         'aliases'
          => [
              'Mcp' => 'Laravel\\Mcp\\Server\\Facades\\Mcp',
          ],
         'providers'
          => [
              0 => 'Laravel\\Mcp\\Server\\McpServiceProvider',
          ],
     ],
    'laravel/pail'
     => [
         'providers'
          => [
              0 => 'Laravel\\Pail\\PailServiceProvider',
          ],
     ],
    'laravel/passport'
     => [
         'providers'
          => [
              0 => 'Laravel\\Passport\\PassportServiceProvider',
          ],
     ],
    'laravel/roster'
     => [
         'providers'
          => [
              0 => 'Laravel\\Roster\\RosterServiceProvider',
          ],
     ],
    'laravel/sail'
     => [
         'providers'
          => [
              0 => 'Laravel\\Sail\\SailServiceProvider',
          ],
     ],
    'laravel/sanctum'
     => [
         'providers'
          => [
              0 => 'Laravel\\Sanctum\\SanctumServiceProvider',
          ],
     ],
    'laravel/tinker'
     => [
         'providers'
          => [
              0 => 'Laravel\\Tinker\\TinkerServiceProvider',
          ],
     ],
    'nesbot/carbon'
     => [
         'providers'
          => [
              0 => 'Carbon\\Laravel\\ServiceProvider',
          ],
     ],
    'nunomaduro/collision'
     => [
         'providers'
          => [
              0 => 'NunoMaduro\\Collision\\Adapters\\Laravel\\CollisionServiceProvider',
          ],
     ],
    'nunomaduro/termwind'
     => [
         'providers'
          => [
              0 => 'Termwind\\Laravel\\TermwindServiceProvider',
          ],
     ],
];
