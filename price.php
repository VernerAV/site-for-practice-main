<?php
// price.php - Публичная страница с прайс-листом в виде таблицы
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Прайс-лист услуг</title>
    <link rel="stylesheet" href="css/price.css">
</head>
<body>
    <!-- Подключаем хедер -->
    <?php include 'templates/header.php'; ?>
    
    <!-- Основной контент -->
    <main class="price-container">
        <!-- Шапка страницы -->
        <div class="price-header">
            <h1>Прайс-лист наших услуг</h1>
            <p class="price-subtitle">Актуальные цены на все виды услуг</p>
            <a href="https://gbu-strogino.ru/wp-content/uploads/2025/11/%D0%9A%D0%BB%D0%B0%D1%81%D1%81%D0%B8%D1%84%D0%B8%D0%BA%D0%B0%D1%82%D0%BE%D1%80-%D0%BF%D0%BB%D0%B0%D1%82%D0%BD%D1%8B%D1%85-%D1%83%D1%81%D0%BB%D1%83%D0%B3-06.11.2025.pdf" target="_blank">Скачать прайс-лист</a>
        </div>
        
        <!-- Поиск -->
        <div class="search-section">
            <div class="search-box">
                <input type="text" 
                       id="searchInput" 
                       placeholder="Поиск по названию или описанию услуги..."
                       class="search-input">
                <button id="clearSearch" class="clear-btn" title="Очистить поиск">×</button>
            </div>
            <div id="searchInfo" class="search-info"></div>
        </div>
          <!-- Информационное примечание -->
        <div class="info-note">
            <div class="note-icon">ℹ️</div>
            <div class="note-content">
                <h3>Информация о ценах</h3>
                <p>Указанные цены являются базовыми и могут меняться в зависимости от сложности и объема работ. 
                Для получения точного расчета обратитесь к нашим специалистам.</p>
            </div>
        </div>
        <!-- Таблица услуг -->
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
                            $counter = 0;
                            foreach ($services as $service) {
                                $counter++;
                                $price_formatted = number_format($service['price'], 0, ',', ' ');
                                $unit = !empty($service['unit']) ? ' / ' . htmlspecialchars($service['unit']) : '';
                                
                                echo '
                                <tr class="service-row" 
                                    data-search="' . htmlspecialchars(strtolower($service['service_name'] . ' ' . $service['description'])) . '"
                                    data-id="' . $service['id'] . '">
                                    <td class="service-cell">
                                        <div class="service-name">' . htmlspecialchars($service['service_name']) . '</div>
                                    </td>
                                    <td class="description-cell">' . nl2br(htmlspecialchars($service['description'])) . '</td>
                                    <td class="price-cell">
                                        <div class="price-amount">' . $price_formatted . ' руб.' . $unit . '</div>
                                        ' . (!empty($service['unit']) ? '<div class="price-note">за ' . htmlspecialchars($service['unit']) . '</div>' : '') . '
                                    </td>
                                </tr>';
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
    
    <!-- Подключаем футер -->
    <?php include 'templates/footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const clearBtn = document.getElementById('clearSearch');
            const searchInfo = document.getElementById('searchInfo');
            const serviceRows = document.querySelectorAll('.service-row');
            const totalRows = serviceRows.length;
            
            // Обновление информации о поиске
            function updateSearchInfo(filteredCount) {
                if (searchInput.value.trim() === '') {
                    searchInfo.textContent = `Всего услуг: ${totalRows}`;
                    searchInfo.className = 'search-info';
                } else {
                    searchInfo.textContent = `Найдено: ${filteredCount} из ${totalRows}`;
                    searchInfo.className = filteredCount === 0 ? 'search-info no-results' : 'search-info has-results';
                }
            }
            
            // Функция поиска
            function performSearch() {
                const searchTerm = searchInput.value.toLowerCase().trim();
                let visibleCount = 0;
                
                serviceRows.forEach(row => {
                    const searchData = row.getAttribute('data-search');
                    const matches = searchData.includes(searchTerm);
                    
                    if (matches || searchTerm === '') {
                        row.style.display = '';
                        visibleCount++;
                        
                        // Подсветка текста
                        if (searchTerm !== '') {
                            highlightMatches(row, searchTerm);
                        } else {
                            removeHighlights(row);
                        }
                    } else {
                        row.style.display = 'none';
                    }
                });
                
                updateSearchInfo(visibleCount);
            }
            
            // Подсветка найденного текста
            function highlightMatches(row, term) {
                const nameCell = row.querySelector('.service-name');
                const descCell = row.querySelector('.description-cell');
                
                if (nameCell) {
                    const original = nameCell.textContent;
                    const regex = new RegExp(`(${term})`, 'gi');
                    const highlighted = original.replace(regex, '<span class="highlight">$1</span>');
                    nameCell.innerHTML = highlighted;
                }
                
                if (descCell) {
                    const original = descCell.textContent;
                    const regex = new RegExp(`(${term})`, 'gi');
                    const highlighted = original.replace(regex, '<span class="highlight">$1</span>');
                    descCell.innerHTML = highlighted;
                }
            }
            
            // Удаление подсветки
            function removeHighlights(row) {
                const nameCell = row.querySelector('.service-name');
                const descCell = row.querySelector('.description-cell');
                
                if (nameCell) {
                    nameCell.innerHTML = nameCell.textContent;
                }
                
                if (descCell) {
                    descCell.innerHTML = descCell.textContent;
                }
            }
            
            // Очистка поиска
            function clearSearch() {
                searchInput.value = '';
                performSearch();
                searchInput.focus();
            }
            
            // События
            searchInput.addEventListener('input', performSearch);
            clearBtn.addEventListener('click', clearSearch);
            
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    clearSearch();
                }
            });
            
            // Инициализация
            updateSearchInfo(totalRows);
            searchInput.focus();
            
            // Добавляем классы для полосатых строк
            serviceRows.forEach((row, index) => {
                if (index % 2 === 0) {
                    row.classList.add('even-row');
                } else {
                    row.classList.add('odd-row');
                }
            });
        });
    </script>
</body>
</html>