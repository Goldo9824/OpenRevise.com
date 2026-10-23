{{-- "Found a mistake?": a small dialog, opened from the page or the right rail. --}}
<div x-data="reportForm('{{ app_url('/_report') }}', {{ $lessonId ?? 'null' }})" class="no-print">
    <div x-cloak x-show="open" x-transition.opacity class="fixed inset-0 z-50 bg-gray-900/40 grid place-items-center p-4" @keydown.escape.window="open = false" @click.self="open = false">
        <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-6" role="dialog" aria-modal="true" aria-labelledby="report-title">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 id="report-title" class="text-lg">Found a mistake?</h2>
                    <p class="text-sm text-muted mt-1">Tell us what is wrong and it will be checked and fixed. No name or email needed.</p>
                </div>
                <button type="button" class="icon-btn -mr-2 -mt-2" @click="open = false" aria-label="Close"><x-glyph name="x" /></button>
            </div>
            <form x-show="status !== 'sent'" @submit.prevent="send" class="mt-4 space-y-3">
                <label for="report-message" class="sr-only">What is wrong?</label>
                <textarea id="report-message" x-ref="message" x-model="message" rows="4" maxlength="2000" required class="answer-box !bg-none !leading-6 !py-2" placeholder="For example: the units in the second formula are the wrong way round."></textarea>
                <div class="flex items-center justify-end gap-2">
                    <button type="button" class="btn btn-quiet btn-sm" @click="open = false">Cancel</button>
                    <button type="submit" class="btn btn-sm" :disabled="status === 'sending'">Send report</button>
                </div>
                <p x-show="status === 'error'" class="text-sm text-danger">That did not send. Please try again in a moment.</p>
            </form>
            <div x-show="status === 'sent'" class="mt-4 flex items-center gap-2 text-sm font-medium text-ok">
                <x-glyph name="check-circle" /> Thank you. It will be checked and fixed.
            </div>
        </div>
    </div>
</div>
