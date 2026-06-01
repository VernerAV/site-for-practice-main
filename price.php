<?php
// price.php - Публичная страница с прайс-листом
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
            <a href="https://gbu-strogino.ru/wp-content/uploads/2025/11/%D0%9A%D0%BB%D0%B0%D1%81%D1%81%D0%B8%D1%84%D0%B8%D0%BA%D0%B0%D1%82%D0%BE%D1%80-%D0%BF%D0%BB%D0%B0%D1%82%D0%BD%D1%8B%D1%85-%D1%83%D1%81%D0%BB%D1%83%D0%B3-06.11.2025.pdf" target="_blank">Скачать прайс-лист</a>
        </div>

        <div class="search-section">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Поиск по названию, описанию или цене..." class="search-input">
                <button id="clearSearch" class="clear-btn" title="Очистить поиск">×</button>
            </div>
            <div id="searchInfo" class="search-info"></div>
        </div>

        <div class="info-note">
            <div class="note-icon">ℹ️</div>
            <div class="note-content">
                <h3>Информация о ценах</h3>
                <p>Указанные цены являются базовыми и могут меняться. Для точного расчета обратитесь к специалистам.</p>
            </div>
        </div>

        <div class="table-container">
            <table class="price-table" id="priceTable">
                <thead>
                    <tr>
                        <th class="col-service">Услуга</th>
                        <th class="col-description">Описание</th>
                        <th class="col-price">Стоимость</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php
                    try {
                        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
                        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        
                        $sql = "SELECT * FROM services ORDER BY service_name";
                        $stmt = $pdo->query($sql);
                        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (empty($services)) {
                            echo '<tr><td colspan="3" class="no-data">Список услуг пуст</td></tr>';
                        } else {
                            foreach ($services as $service) {
                                // Обработка цены – если NULL или 0, выводим "договорная"
                                $price_value = isset($service['price']) && $service['price'] !== null ? (float)$service['price'] : 0;
                                if ($price_value > 0) {
                                    $price_display = number_format($price_value, 0, ',', ' ') . ' руб.';
                                    if (!empty($service['unit'])) {
                                        $price_display .= ' / ' . htmlspecialchars($service['unit']);
                                    }
                                    // Для поиска по цене: числовое значение
                                    $price_search = $price_value;
                                } else {
                                    $price_display = 'договорная';
                                    $price_search = 'договорная';
                                }
                                
                                $name = htmlspecialchars($service['service_name']);
                                $desc = htmlspecialchars($service['description']);
                                $search_data = strtolower($name . ' ' . $desc . ' ' . $price_search);
                                ?>
                                <tr class="service-row" data-search="<?= htmlspecialchars($search_data) ?>" data-id="<?= $service['id'] ?>">
                                    <td class="service-cell">
                                        <div class="service-name"><?= $name ?></div>
                                    </td>
                                    <td class="description-cell"><?= nl2br($desc) ?></td>
                                    <td class="price-cell">
                                        <div class="price-amount"><?= $price_display ?></div>
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                    } catch (PDOException $e) {
                        echo '<tr><td colspan="3" class="error-message">Ошибка загрузки данных: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </main>

    <?php include 'templates/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const clearBtn = document.getElementById('clearSearch');
            const searchInfo = document.getElementById('searchInfo');
            const serviceRows = document.querySelectorAll('.service-row');
            const totalRows = serviceRows.length;

            function updateSearchInfo(filteredCount) {
                if (searchInput.value.trim() === '') {
                    searchInfo.textContent = `Всего услуг: ${totalRows}`;
                    searchInfo.className = 'search-info';
                } else {
                    searchInfo.textContent = `Найдено: ${filteredCount} из ${totalRows}`;
                    searchInfo.className = filteredCount === 0 ? 'search-info no-results' : 'search-info has-results';
                }
            }

            function highlightMatches(row, term) {
                // Убираем уже существующую подсветку
                removeHighlights(row);
                if (!term) return;
                
                const nameCell = row.querySelector('.service-name');
                const descCell = row.querySelector('.description-cell');
                const priceCell = row.querySelector('.price-amount');
                const regex = new RegExp(`(${term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
                
                if (nameCell) {
                    const original = nameCell.textContent;
                    nameCell.innerHTML = original.replace(regex, '<span class="highlight">$1</span>');
                }
                if (descCell) {
                    const original = descCell.textContent;
                    descCell.innerHTML = original.replace(regex, '<span class="highlight">$1</span>');
                }
                if (priceCell && term.match(/^\d+$/) && priceCell.textContent !== 'договорная') {
                    const original = priceCell.textContent;
                    priceCell.innerHTML = original.replace(regex, '<span class="highlight">$1</span>');
                }
            }

            function removeHighlights(row) {
                const nameCell = row.querySelector('.service-name');
                const descCell = row.querySelector('.description-cell');
                const priceCell = row.querySelector('.price-amount');
                if (nameCell) nameCell.innerHTML = nameCell.textContent;
                if (descCell) descCell.innerHTML = descCell.textContent;
                if (priceCell) priceCell.innerHTML = priceCell.textContent;
            }

            function performSearch() {
                const searchTerm = searchInput.value.trim().toLowerCase();
                let visibleCount = 0;
                
                serviceRows.forEach(row => {
                    const searchData = row.getAttribute('data-search');
                    let matches = searchData.includes(searchTerm);
                    
                    // Дополнительно: если поиск по числу, пробуем извлечь цену из data-search (после последнего пробела)
                    if (!matches && /^\d+$/.test(searchTerm)) {
                        const priceMatch = searchData.match(/\s(\d+(?:\.\d+)?)$/);
                        if (priceMatch && priceMatch[1] === searchTerm) matches = true;
                    }
                    
                    if (matches || searchTerm === '') {
                        row.style.display = '';
                        visibleCount++;
                        if (searchTerm !== '') highlightMatches(row, searchTerm);
                        else removeHighlights(row);
                    } else {
                        row.style.display = 'none';
                        removeHighlights(row);
                    }
                });
                updateSearchInfo(visibleCount);
            }

            function clearSearch() {
                searchInput.value = '';
                performSearch();
                searchInput.focus();
            }

            searchInput.addEventListener('input', performSearch);
            clearBtn.addEventListener('click', clearSearch);
            searchInput.addEventListener('keydown', e => { if (e.key === 'Escape') clearSearch(); });

            updateSearchInfo(totalRows);
            searchInput.focus();

            // Чередование цветов строк
            serviceRows.forEach((row, idx) => {
                row.classList.add(idx % 2 === 0 ? 'even-row' : 'odd-row');
            });
        });
    </script>
</body>
</html>