import { Controller } from '@hotwired/stimulus';
import {
    Chessboard,
    INPUT_EVENT_TYPE,
    COLOR,
    BORDER_TYPE,
    FEN,
    PIECE,
} from 'cm-chessboard';

const ASSETS_URL = '/assets/cm-chessboard/';

export default class extends Controller {
    static values  = { fen: String, moves: String, userSide: String };
    static targets = ['board', 'status'];

    async connect() {
        this.expected = this.movesValue.trim().split(/\s+/).filter(Boolean);
        this.idx = 0;
        this.solved = false;

        this.userColor = this.userSideValue === 'w' ? COLOR.white : COLOR.black;

        this.setStatus('Načítám…');

        try {
            this.board = new Chessboard(this.boardTarget, {
                position: this.fenValue,
                assetsUrl: ASSETS_URL,
                orientation: this.userColor,
                style: {
                    cssClass: 'default',
                    borderType: BORDER_TYPE.thin,
                },
            });

            await this.board.initialized;
            await this.delay(400);
            await this.playOpponentMove();
            this.setStatus('Vaše tahy.');
            this.board.enableMoveInput((e) => this.onMoveInput(e), this.userColor);
        } catch (err) {
            console.error('cm-chessboard init failed', err);
            this.setStatus('Chyba při načítání šachovnice (viz konzole).', 'fail');
        }
    }

    delay(ms) {
        return new Promise((resolve) => setTimeout(resolve, ms));
    }

    async playOpponentMove() {
        if (this.idx >= this.expected.length) return;
        const uci = this.expected[this.idx++];
        await this.board.movePiece(uci.slice(0, 2), uci.slice(2, 4), true);
        // Promotion: cm-chessboard movePiece doesn't take a promo arg directly;
        // for this flow opponent-promo moves are rare; if needed, set the promoted piece manually.
        if (uci.length === 5) {
            const promo = uci[4];
            const target = uci.slice(2, 4);
            const color = this.userColor === COLOR.white ? 'b' : 'w';
            await this.board.setPiece(target, color + promo, true);
        }
    }

    onMoveInput(event) {
        if (event.type === INPUT_EVENT_TYPE.moveInputStarted) {
            return !this.solved;
        }
        if (event.type !== INPUT_EVENT_TYPE.validateMoveInput) {
            return true;
        }
        if (this.solved) return false;

        const want = this.expected[this.idx];
        if (!want) return false;

        const playedBase = event.squareFrom + event.squareTo;
        const wantBase   = want.slice(0, 4);

        if (playedBase !== wantBase) {
            this.setStatus('✗ Špatný tah, zkuste znovu', 'fail');
            return false;
        }

        // Promotion: if expected has 5 chars, prompt-default to expected promo.
        if (want.length === 5 && !event.promotion) {
            // cm-chessboard fires promotionDialogResult separately; we'll auto-apply the expected promotion.
            // For simplicity in this puzzle flow, accept the move and replace piece below.
        }

        this.idx++;

        // Apply expected promotion replacement after the move is rendered.
        if (want.length === 5) {
            const promo = want[4];
            const dst = want.slice(2, 4);
            setTimeout(() => this.board.setPiece(dst, (this.userColor === COLOR.white ? 'w' : 'b') + promo, true), 50);
        }

        if (this.idx >= this.expected.length) {
            this.solved = true;
            this.setStatus('✓ Vyřešeno!', 'done');
            return true;
        }

        this.setStatus('✓ Správně, pokračujte…', 'ok');
        setTimeout(() => {
            this.playOpponentMove().then(() => {
                if (!this.solved) this.setStatus('Vaše tahy.');
            });
        }, 350);
        return true;
    }

    async reset() {
        this.idx = 0;
        this.solved = false;
        this.board.disableMoveInput();
        await this.board.setPosition(this.fenValue, true);
        this.setStatus('Načítám…');
        await this.delay(400);
        await this.playOpponentMove();
        this.setStatus('Vaše tahy.');
        this.board.enableMoveInput((e) => this.onMoveInput(e), this.userColor);
    }

    setStatus(text, kind = '') {
        this.statusTarget.textContent = text;
        this.statusTarget.className = kind ? 'status-' + kind : '';
    }
}
