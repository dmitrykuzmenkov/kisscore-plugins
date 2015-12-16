<?php namespace Plugin\SEO;
class Sitemap {
  public static function generate($file, array $locs) {
    file_put_contents($file, '<?xml version="1.0" encoding="UTF-8"?>'
      . PHP_EOL . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:mobile="http://www.google.com/schemas/sitemap-mobile/1.0">'
      . PHP_EOL . '<url>'
      . PHP_EOL . '  <loc>http://' . config('common.domain') . '</loc>'
      . PHP_EOL . '  <lastmod>' . date('Y-m-d') . '</lastmod>'
      . PHP_EOL . '  <priority>1.0</priority>'
      . PHP_EOL . '</url>'
      . PHP_EOL . '<url>'
      . PHP_EOL . '  <loc>http://' . config('common.domain') . '</loc>'
      . PHP_EOL . '  <lastmod>' . date('Y-m-d') . '</lastmod>'
      . PHP_EOL . '  <priority>1.0</priority>'
      . PHP_EOL . '  <mobile:mobile/>'
      . PHP_EOL . '</url>'
      . PHP_EOL . implode('',
        array_map(function ($loc) {
          return
            PHP_EOL . '<url>'
            . PHP_EOL . '  <loc>' . $loc['url'] . '</loc>'
            . PHP_EOL . '  <lastmod>' . $loc['date'] . '</lastmod>'
            . PHP_EOL . '  <priority>' . $loc['priority'] . '</priority>'
            . PHP_EOL . '</url>'
          ;
        }, $locs)
      )
      . PHP_EOL . '</urlset>'
    );
  }
}
