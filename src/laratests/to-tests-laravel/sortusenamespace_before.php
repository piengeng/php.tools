<?php

namespace Illuminate\Foundation;

use Closure;
use Illuminate\Config\FileEnvironmentVariablesLoader;
use Illuminate\Config\FileLoader;
use Illuminate\Container\Container;
use Illuminate\Events\EventServiceProvider;
use Illuminate\Exception\ExceptionServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\RoutingServiceProvider;
use Illuminate\Support\Contracts\ResponsePreparerInterface;
use Illuminate\Support\Facades\Facade;
use Stack\Builder;
use Symfony\Component\Debug\Exception\FatalErrorException;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
