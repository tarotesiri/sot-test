<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : Router.php
 *    Description : ระบบ Route: GET/POST, พารามิเตอร์ {slug} / {id:\d+}, ตั้งชื่อ Route, Group + Middleware และตรวจ CSRF อัตโนมัติทุก POST
 * ============================================================================
 *    Author : TaRote SiRi
 *    Email : *******.***@*****.***
 *    Created Date : 2026-11-01
 *    Version : 1.0.0
 *    License : Proprietary (ใช้ภายในองค์กร Siridev.Com เท่านั้น)
 * ============================================================================
**/
declare(strict_types=1);

namespace App\Core;

/**
 * ตัวอย่าง:
 *   $r->get('/courses/{slug}', [CourseController::class, 'show'], 'courses.show');
 *   $r->get('/orders/{id:\d+}', [...]);
 *   $r->group('/admin', function (Router $r) { $r->get('/', [...]); }, [$authAdmin]);
 *   $r->post('/cart/add', [...])->middleware($authUser);
 *
 * Middleware = callable(Request $request): mixed
 *   - คืน string  = ส่งผลลัพธ์นั้นแทน (หยุดไม่เรียก Controller)
 *   - คืน null    = ไปต่อ
 *   - หรือ redirect()/abort() ได้เลย
 *
 * หมายเหตุ: ชื่อพารามิเตอร์ใน Route ต้องตรงกับชื่อตัวแปรใน method ของ Controller
 */
final class Router
{
    private array $routes = [];
    private array $named = [];
    private string $groupPrefix = '';
    private array $groupMiddleware = [];
    private ?string $lastKey = null;

    public function get(string $path, array|\Closure $handler, ?string $name = null): self
    {
        return $this->add(['GET', 'HEAD'], $path, $handler, $name);
    }

    public function post(string $path, array|\Closure $handler, ?string $name = null): self
    {
        return $this->add(['POST'], $path, $handler, $name);
    }

    public function add(array $methods, string $path, array|\Closure $handler, ?string $name = null): self
    {
        $full = '/' . trim(trim($this->groupPrefix, '/') . '/' . trim($path, '/'), '/');
        $regex = preg_replace_callback(
            '#\{([a-zA-Z_]\w*)(?::([^}]+))?\}#',
            static fn (array $m): string => '(?P<' . $m[1] . '>' . (($m[2] ?? '') !== '' ? $m[2] : '[^/]+') . ')',
            $full
        );
        $key = implode('|', $methods) . ' ' . $full; // key ซ้ำ = Module ที่โหลดทีหลังแทนที่
        $this->routes[$key] = [
            'methods'    => $methods,
            'path'       => $full,
            'regex'      => '#^' . $regex . '$#u',
            'handler'    => $handler,
            'middleware' => $this->groupMiddleware,
        ];
        $this->lastKey = $key;
        if ($name !== null) {
            $this->named[$name] = $full;
        }
        return $this;
    }

    /** เพิ่ม Middleware ให้ Route ล่าสุด */
    public function middleware(callable ...$middleware): self
    {
        if ($this->lastKey !== null) {
            array_push($this->routes[$this->lastKey]['middleware'], ...$middleware);
        }
        return $this;
    }

    public function group(string $prefix, callable $define, array $middleware = []): void
    {
        $prevPrefix = $this->groupPrefix;
        $prevMw = $this->groupMiddleware;
        $this->groupPrefix = rtrim($prevPrefix, '/') . '/' . trim($prefix, '/');
        $this->groupMiddleware = [...$prevMw, ...$middleware];
        try {
            $define($this);
        } finally {
            $this->groupPrefix = $prevPrefix;
            $this->groupMiddleware = $prevMw;
        }
    }

    public function has(string $name): bool
    {
        return isset($this->named[$name]);
    }

    public function url(string $name, array $params = []): string
    {
        if (!isset($this->named[$name])) {
            throw new \InvalidArgumentException("ไม่พบ route ชื่อ {$name}");
        }
        $path = preg_replace_callback(
            '#\{([a-zA-Z_]\w*)(?::[^}]+)?\}#',
            static function (array $m) use (&$params): string {
                $v = $params[$m[1]] ?? '';
                unset($params[$m[1]]);
                return rawurlencode((string) $v);
            },
            $this->named[$name]
        );
        return url((string) $path) . ($params ? '?' . http_build_query($params) : '');
    }

    /** รายการ Route ทั้งหมด (ใช้แสดงในหน้า System Status) */
    public function list(): array
    {
        return array_values(array_map(
            static fn (array $r): array => ['methods' => $r['methods'], 'path' => $r['path']],
            $this->routes
        ));
    }

    public function dispatch(Request $request): mixed
    {
        $method = $request->method();
        $path = $request->path();
        $allowed = [];

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $m)) {
                continue;
            }
            if (!in_array($method, $route['methods'], true)) {
                $allowed = array_merge($allowed, $route['methods']);
                continue;
            }
            if ($method === 'POST' && !Csrf::verify($request->input('_token') ?? $request->header('X-CSRF-Token'))) {
                throw new HttpException(419, 'แบบฟอร์มหมดอายุหรือไม่ถูกต้อง กรุณารีเฟรชหน้าแล้วลองใหม่อีกครั้ง');
            }
            foreach ($route['middleware'] as $mw) {
                $result = $mw($request);
                if (is_string($result)) {
                    return $result;
                }
            }
            $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
            return $this->invoke($route['handler'], $params);
        }

        if ($allowed) {
            header('Allow: ' . implode(', ', array_unique($allowed)));
            throw new HttpException(405);
        }
        throw new HttpException(404);
    }

    private function invoke(array|\Closure $handler, array $params): mixed
    {
        if ($handler instanceof \Closure) {
            return $handler(...$params);
        }
        [$class, $method] = $handler + [null, null];
        if (!is_string($class) || !class_exists($class) || !is_string($method) || !method_exists($class, $method)) {
            throw new \RuntimeException('Route handler ไม่ถูกต้อง: ' . json_encode($handler));
        }
        return (new $class(App::instance()))->{$method}(...$params);
    }
}