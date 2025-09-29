<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Бредоген - генератор ультракоротких ржачных текстов</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        header, footer{
            background: rgb(35, 86, 138) !important;
        }
        .sentence-display {
            font-size: 28px;
            font-weight: bold;
            min-height: 80px;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        .copy-btn, .like-btn, .share-btn {
            cursor: pointer;
            transition: all 0.3s;
        }
        .copy-btn:hover {
            color: #0d6efd;
        }
        .like-btn:hover {
            color: #dc3545;
        }
        .share-btn:hover {
            color: #198754;
        }
        .like-btn.liked {
            color: #dc3545;
        }
        .like-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .top-likes-list {
            max-height: 400px;
            overflow-y: auto;
        }
        .share-url-container {
            background: #f8f9fa;
            border-radius: 5px;
            padding: 10px;
            margin-top: 10px;
            display: none;
        }
        .share-link {
            word-break: break-all;
            font-size: 14px;
        }
        .top-like-item {
            text-decoration: none;
            color: inherit;
            transition: all 0.3s;
        }
        .top-like-item:hover {
            background-color: #f8f9fa;
            color: inherit;
        }
    </style>
</head>
<body>
<!-- Шапка -->
<header class="text-white py-3">
    <div class="container">
        <h1 class="text-center">Бредоген</h1>
        <p class="text-center mb-0">Генератор ультракоротких ржачных текстов</p>
    </div>
</header>

<!-- Основной контент -->
<main class="container my-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <!-- Генератор -->
            <div class="card shadow">
                <div class="card-body text-center">
                    <h2 class="card-title mb-4">Сгенерируйте смешное предложение</h2>

                    <div class="sentence-display d-flex align-items-center justify-content-center">
                        <span id="sentence-text">{{ $sentence['sentence'] ?? 'Нажмите кнопку для генерации' }}</span>
                    </div>

                    <div class="d-flex justify-content-center gap-3 mb-3">
                        <button class="btn btn-primary" onclick="generateSentence()">
                            <i class="fas fa-sync-alt me-2"></i>Сгенерировать новую
                        </button>

                        <button class="btn btn-outline-secondary copy-btn" onclick="copySentence()"
                                title="Скопировать текст">
                            <i class="fas fa-copy"></i>
                        </button>

                        <button class="btn btn-outline-danger like-btn" onclick="likeSentence()"
                                id="like-btn" title="Поставить лайк">
                            <i class="fas fa-heart"></i>
                        </button>

                        <button class="btn btn-outline-success share-btn" onclick="shareSentence()"
                                title="Поделиться ссылкой">
                            <i class="fas fa-share-alt"></i>
                        </button>
                    </div>

                    <!-- Контейнер для ссылки -->
                    <div id="share-url-container" class="share-url-container">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="share-link" id="share-url"></span>
                            <button class="btn btn-sm btn-outline-primary ms-2" onclick="copyShareUrl()">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>

                    <div id="message" class="alert alert-success d-none"></div>
                </div>
            </div>

            <!-- Топ лайков -->
            <div class="card shadow mt-4">
                <div class="card-body">
                    <h3 class="card-title">Топ 30 по лайкам</h3>
                    <div class="top-likes-list" id="top-likes">
                        @foreach($topLikes as $item)
                            <a href="{{ $item['share_url'] }}" class="top-like-item d-block border-bottom py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">{{ $item['sentence'] }}</span>
                                    <span class="badge bg-danger">{{ $item['like_count'] }}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Подвал -->
<footer class="text-white py-4 mt-5">
    <div class="container text-center">
        <p class="mb-0">&copy; 2025 Бредоген - это генератор ультракороткых смешных фраз. Автор проекта - <a href="https://github.com/homoastricus">https://github.com/homoastricus</a></p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let currentWords = @json($sentence['words'] ?? []);

    // При загрузке страницы инициализируем кнопку лайка как активную
    document.addEventListener('DOMContentLoaded', function() {
        // Всегда разрешаем ставить лайк при загрузке страницы
        resetUI();

        // Если есть слова в currentWords, значит страница загружена с конкретной генерацией
        if (currentWords.nn_id) {
            console.log('Загружена существующая генерация, лайк можно поставить');
        }
    });

    async function generateSentence() {
        try {
            const response = await fetch('{{ route("generate") }}');
            const data = await response.json();

            document.getElementById('sentence-text').textContent = data.sentence.sentence;
            currentWords = data.sentence.words;

            // Очищаем URL параметры при генерации новой фразы
            window.history.replaceState({}, document.title, window.location.pathname);

            // Сбрасываем состояние - разрешаем лайк
            resetUI();
            hideShareUrl();

            // Обновляем топ лайков
            updateTopLikes(data.topLikes);

        } catch (error) {
            console.error('Ошибка:', error);
        }
    }

    async function shareSentence() {
        if (!currentWords.nn_id) {
            showMessage('Сначала сгенерируйте предложение!', 'warning');
            return;
        }

        try {
            const response = await fetch('{{ route("generate.share.link") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(currentWords)
            });

            const data = await response.json();

            if (response.ok) {
                const shareUrlContainer = document.getElementById('share-url-container');
                const shareUrlElement = document.getElementById('share-url');

                shareUrlElement.textContent = data.share_url;
                shareUrlContainer.style.display = 'block';

                // Прокручиваем к ссылке
                shareUrlContainer.scrollIntoView({ behavior: 'smooth' });

                showMessage('Ссылка для общего доступа создана!');
            } else {
                showMessage('Ошибка при создании ссылки', 'danger');
            }
        } catch (error) {
            console.error('Ошибка:', error);
            showMessage('Ошибка при создании ссылки', 'danger');
        }
    }

    async function likeSentence() {
        if (!currentWords.nn_id) {
            showMessage('Сначала сгенерируйте предложение!', 'warning');
            return;
        }

        try {
            const response = await fetch('{{ route("like") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(currentWords)
            });

            const data = await response.json();

            if (response.ok) {
                // Лайк успешно поставлен
                document.getElementById('like-btn').classList.add('liked');
                document.getElementById('like-btn').disabled = true;
                showMessage('Лайк поставлен!');

                // Обновляем топ лайков
                const generateResponse = await fetch('{{ route("generate") }}');
                const generateData = await generateResponse.json();
                updateTopLikes(generateData.topLikes);
            } else {
                // Лайк уже был поставлен (или другая ошибка)
                document.getElementById('like-btn').classList.add('liked');
                document.getElementById('like-btn').disabled = true;
                showMessage(data.error || 'Лайк уже был поставлен', 'warning');
            }
        } catch (error) {
            console.error('Ошибка:', error);
            showMessage('Ошибка при постановке лайка', 'danger');
        }
    }

    async function copySentence() {
        const sentence = document.getElementById('sentence-text').textContent;
        try {
            await navigator.clipboard.writeText(sentence);
            showMessage('Текст скопирован в буфер обмена!');
        } catch (err) {
            console.error('Ошибка копирования:', err);
        }
    }

    async function copyShareUrl() {
        const shareUrl = document.getElementById('share-url').textContent;
        try {
            await navigator.clipboard.writeText(shareUrl);
            showMessage('Ссылка скопирована в буфер обмена!');
        } catch (err) {
            console.error('Ошибка копирования:', err);
        }
    }

    function updateTopLikes(topLikes) {
        const container = document.getElementById('top-likes');
        container.innerHTML = '';

        topLikes.forEach(item => {
            const link = document.createElement('a');
            link.href = item.share_url;
            link.className = 'top-like-item d-block border-bottom py-2';
            link.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold">${item.sentence}</span>
                    <span class="badge bg-danger">${item.like_count}</span>
                </div>
            `;
            container.appendChild(link);
        });
    }

    function resetUI() {
        // Всегда сбрасываем кнопку лайка в активное состояние
        document.getElementById('like-btn').classList.remove('liked');
        document.getElementById('like-btn').disabled = false;
        hideShareUrl();
    }

    function hideShareUrl() {
        document.getElementById('share-url-container').style.display = 'none';
    }

    function showMessage(text, type = 'success') {
        const messageEl = document.getElementById('message');
        messageEl.textContent = text;
        messageEl.className = `alert alert-${type}`;
        messageEl.classList.remove('d-none');

        setTimeout(() => {
            messageEl.classList.add('d-none');
        }, 3000);
    }
</script>
</body>
</html>
