<div class="event-modal" id="eventModal">
    <div class="event-modal-content">
        <div class="event-author author-sticky" id="eventAuthorMobile">
            <img class="author-avatar" id="authorAvatarMobile">
            <div class="author-info">
                <span class="author-name" id="modalAuthorNameMobile"></span>
                <span class="author-role">Автор події</span>
            </div>
        </div>
        <span class="close-modal">&times;</span>

        <!-- 🧱 Один лейаут для всіх пристроїв -->
        <div class="event-layout">

            <!-- ⬅️ ЛІВО: ПРО ПОДІЮ -->
            <div class="event-left">


<div class="img_and_info">
                <!-- Зображення (адаптивне) -->
                <div class="event-image-container">
                    <img id="modalImage" src="" alt="" class="event-main-image">
                </div>

<div class="info-event">
                <!-- Заголовок та категорія -->
                <div class="event-header">
                    <div class="event-title-block">
                        <h3 id="modalTitle"></h3>
                        <p class="modal-category"></p>
                    </div>
                </div>
                <!-- Інформація (адаптивна сітка) -->
                <div class="event-info-grid">
                    <div class="info-item modal-location">
                        <span class="info-icon">📍</span>
                        <span class="info-text"></span>
                    </div>
                    <div class="info-item modal-date">
                        <span class="info-icon">📅</span>
                        <span class="info-text"></span>
                    </div>
                    <div class="info-item modal-time">
                        <span class="info-icon">🕒</span>
                        <span class="info-text"></span>
                    </div>

                </div>

</div>
</div>

                <!-- Опис події -->
                <div class="event-details-description">
                    <h4 class="description-title">📝 Про подію</h4>
                    <div id="modalDescription" class="description-content"></div>
                </div>
                <div class="end"></div>

            </div>

            <!-- ➡️ ПРАВО: META (сховається на мобільних) -->
            <div class="event-right">
                <!-- Автор (для ПК та мобільних) -->
                <div class="event-author" id="eventAuthorDesktop">
                    <img class="author-avatar" id="authorAvatarDesktop">
                    <div class="author-info">
                        <span class="author-name" id="modalAuthorNameDesktop"></span>
                        <span class="author-role">Автор події</span>
                    </div>
                </div>
                <!-- коментарі -->
                <div class="event-side-card comments-section">
                    <h4 class="comments-title">Коментарі</h4>

                    <div class="comments-list" id="commentsList"></div>


                    <div class="event-actions" id="eventActions">

                        <button class="event-action" data-action="like">
                            <svg viewBox="0 0 24 24" class="icon">
                                <svg viewBox="0 0 24 24" class="icon">
                                    <path d="M12 21s-7-4.2-9.2-8.3C1 9 3.5 6 6.8 6c2 0 3.4 1.2 5.2 3 1.8-1.8 3.2-3 5.2-3C20.5 6 23 9 21.2 12.7 19 16.8 12 21 12 21z"/>
                                </svg>
                            <span data-likes-count>0</span>
                        </button>

                        <button class="event-action" data-action="comment">
                            <svg viewBox="0 0 24 24" class="icon">
                                <path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/>
                            </svg>
                            <span data-comments-count>0</span>
                        </button>

                        <button class="event-action" data-action="save" data-event-id="">
                            <svg viewBox="0 0 24 24" class="icon" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 3h12a1.5 1.5 0 0 1 1.5 1.5v16.5L12 17l-7.5 4V4.5A1.5 1.5 0 0 1 6 3z"/>
                            </svg>

                        </button>

                        <button class="event-action" data-action="share">
                            <svg viewBox="0 0 24 24" class="icon">
                                <circle cx="18" cy="5" r="3"/>
                                <circle cx="6" cy="12" r="3"/>
                                <circle cx="18" cy="19" r="3"/>
                                <path d="M8.5 13.5l7 4"/>
                                <path d="M15.5 6.5l-7 4"/>
                            </svg>
                        </button>

                    </div>

                    <!-- Поле вводу -->
                    <div class="comment-input">
                        <input type="text" id="commentText" placeholder="Написати коментар...">
                        <button id="sendComment" type="button">➤</button>
                    </div>
                </div>
            </div>

        </div>


        <div class="event-actions" id="eventActionsMob">

            <button class="event-action" data-action="like">
                <svg viewBox="0 0 24 24" class="icon">
                    <path d="M12 21s-7-4.2-9.2-8.3C1 9 3.5 6 6.8 6c2 0 3.4 1.2 5.2 3 1.8-1.8 3.2-3 5.2-3C20.5 6 23 9 21.2 12.7 19 16.8 12 21 12 21z"/>
                </svg>

                <span data-likes-count>0</span>
            </button>

            <button class="event-action" data-action="comment">
                <svg viewBox="0 0 24 24" class="icon">
                    <path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/>
                </svg>
                <span data-comments-count>0</span>
            </button>

            <button class="event-action" data-action="save" data-event-id="">
            <svg viewBox="0 0 24 24" class="icon" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 3h12a1.5 1.5 0 0 1 1.5 1.5v16.5L12 17l-7.5 4V4.5A1.5 1.5 0 0 1 6 3z"/>
                </svg>
            </button>

            <button class="event-action" data-action="share">
                <svg viewBox="0 0 24 24" class="icon">
                    <circle cx="18" cy="5" r="3"/>
                    <circle cx="6" cy="12" r="3"/>
                    <circle cx="18" cy="19" r="3"/>
                    <path d="M8.5 13.5l7 4"/>
                    <path d="M15.5 6.5l-7 4"/>
                </svg>
            </button>

        </div>

    </div>
</div>