<div class="event-modal" id="eventModal">
    <div class="event-modal-content">

        <span class="close-modal">&times;</span>

        <!-- 🧱 NEW layout wrapper -->
        <div class="event-layout">

            <!-- ⬅️ ЛІВО: ПРО ПОДІЮ -->
            <div class="event-left">

                <div class="event-header">
                    <img id="modalImage" src="" alt="">
                    <div class="event-title-block">
                        <h3 id="modalTitle"></h3>
                        <p class="modal-category"></p>
                    </div>
                </div>

                <div class="event-info-grid">
                    <div class="info-item modal-location"></div>
                    <div class="info-item modal-date"></div>
                    <div class="info-item modal-time"></div>
                </div>
                <div class=".event-details-description">
                    <h4>Про подію</h4>
                    <div id="modalDescription"></div>
                </div>

            </div>

            <!-- ➡️ ПРАВО: META -->
            <div class="event-right">

                <!-- автор (JS вже працює з ним) -->
                <div class="author-badge" id="authorBadge" style="display:none;">
                    <span class="author-icon">👤</span>
                    <span class="author-name" id="modalAuthorName"></span>
                </div>




                <!-- коментарі -->
                <div class="event-side-card comments-section">
                    <h4>Коментарі</h4>

                    <div class="comments-list">
                        <!-- JS / backend -->
                        daf
                        dsa
                    </div>
                    <!-- реакції -->
                    <div class="event-stats">
                        <div class="event-stat">
                            ❤️ <span>24</span>
                        </div>
                        <div class="event-stat">
                            💬 <span>8</span>
                        </div>
                    </div>
                    <div class="comment-input">
                        <input type="text" placeholder="Написати коментар...">
                        <button>➤</button>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>
