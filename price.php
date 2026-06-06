<?php
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Прайс-лист услуг</title>
    <link rel="stylesheet" href="css/header_mobile.css">
    <link rel="stylesheet" href="css/style_mobile.css">
    <link rel="stylesheet" href="css/price.css">
</head>
<body>
    <?php include 'templates/header.php'; ?>

    <main class="price-container">
        <div class="price-header">
            <h1>Прайс-лист наших услуг</h1>
            <p class="price-subtitle">Актуальные цены на все виды услуг</p>
            <a href="https://gbu-strogino.ru/wp-content/uploads/2025/11/%D0%9A%D0%BB%D0%B0%D1%81%D1%81%D0%B8%D1%84%D0%B8%D0%BA%D0%B0%D1%82%D0%BE%D1%80-%D0%BF%D0%BB%D0%B0%D1%82%D0%BD%D1%8B%D1%85-%D1%83%D1%81%D0%BB%D1%83%D0%B3-06.11.2025.pdf" target="_blank" class="download-link">📄 Скачать прайс-лист (PDF)</a>
        </div>

        <!-- ПОЛЕ ПОИСКА ДЛЯ ФИЛЬТРАЦИИ ТАБЛИЦЫ -->
        <div class="search-section">
            <div class="price-search-box">
                <input type="text" id="priceSearchInput" class="price-search-input" placeholder="Поиск по услугам (название, описание, цена)" autocomplete="off">
                <button id="priceClearBtn" class="price-clear-btn" title="Очистить">×</button>
            </div>
            <div id="priceSearchInfo" class="search-info"></div>
        </div>

        <div class="table-container">
            <table class="price-table">
                <thead>
                    <tr>
                        <th>Услуга</th>
                        <th>Описание</th>
                        <th>Стоимость</th>
                    </tr>
                </thead>
                <tbody id="priceTableBody">
                    <?php
                    try {
                        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
                        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        $services = $pdo->query("SELECT * FROM services ORDER BY service_name")->fetchAll();
                        
                        if (empty($services)) {
                            echo '<tr><td colspan="3" class="no-data">Нет услуг</td></tr>';
                        } else {
                            foreach ($services as $service) {
                                $price_value = isset($service['price']) && $service['price'] > 0 ? (float)$service['price'] : 0;
                                $price_display = ($price_value > 0) ? number_format($price_value, 0, ',', ' ') . ' руб.' . ($service['unit'] ? ' / ' . htmlspecialchars($service['unit']) : '') : 'договорная';
                                $search_string = mb_strtolower($service['service_name'] . ' ' . $service['description'] . ' ' . ($price_value > 0 ? $price_value : 'договорная'), 'UTF-8');
                                ?>
                                <tr class="price-service-row" data-search="<?= htmlspecialchars($search_string, ENT_QUOTES, 'UTF-8') ?>">
                                    <td><strong><?= htmlspecialchars($service['service_name']) ?></strong></td>
                                    <td><?= nl2br(htmlspecialchars($service['description'])) ?></td>
                                    <td class="price-cell"><?= $price_display ?></td>
                                </tr>
                                <?php
                            }
                        }
                    } catch (PDOException $e) {
                        echo '<tr><td colspan="3" class="error-message">Ошибка: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </main>

    <?php include 'templates/footer.php'; ?>

    <script>
        // Простой и надёжный поиск по таблице прайс-листа
        (function() {
            // Ждём полной загрузки страницы
            document.addEventListener('DOMContentLoaded', function() {
                const input = document.getElementById('priceSearchInput');
                const clearBtn = document.getElementById('priceClearBtn');
                const info = document.getElementById('priceSearchInfo');
                const rows = document.querySelectorAll('#priceTableBody .price-service-row');
                
                if (!input || rows.length === 0) return;
                
                const totalRows = rows.length;
                
                // Функция обновления счётчика
                function updateInfo(visibleCount) {
                    const term = input.value.trim();
                    if (term === '') {
                        info.textContent = 'Всего услуг: ' + totalRows;
                        info.classList.remove('no-results');
                    } else {
                        info.textContent = 'Найдено: ' + visibleCount + ' из ' + totalRows;
                        if (visibleCount === 0) {
                            info.classList.add('no-results');
                        } else {
                            info.classList.remove('no-results');
                        }
                    }
                }
                
                // Функция фильтрации
                function filterTable() {
                    const searchTerm = input.value.trim().toLowerCase();
                    let visibleCount = 0;
                    
                    for (let i = 0; i < rows.length; i++) {
                        const row = rows[i];
                        const searchData = row.getAttribute('data-search') || '';
                        
                        if (searchTerm === '' || searchData.includes(searchTerm)) {
                            row.style.display = '';
                            visibleCount++;
                        } else {
                            row.style.display = 'none';
                        }
                    }
                    
                    updateInfo(visibleCount);
                }
                
                // Очистка поиска
                function clearSearch() {
                    input.value = '';
                    filterTable();
                    input.focus();
                }
                
                // Назначаем обработчики
                input.addEventListener('input', filterTable);
                if (clearBtn) {
                    clearBtn.addEventListener('click', clearSearch);
                }
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') clearSearch();
                });
                
                // Автоматическая фильтрация из GET-параметра "search"
                const urlParams = new URLSearchParams(window.location.search);
                const searchParam = urlParams.get('search');
                if (searchParam) {
                    input.value = decodeURIComponent(searchParam);
                    filterTable();
                } else {
                    updateInfo(totalRows);
                }
            });
        })();
    </script>
</body>
</html>