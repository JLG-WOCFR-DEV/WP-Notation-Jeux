const ORIGINAL_READY_STATE = Object.getOwnPropertyDescriptor(document, 'readyState');

describe('Onboarding tracking instrumentation', () => {
  let readyStateValue = 'loading';

  beforeEach(() => {
    jest.resetModules();

    readyStateValue = 'loading';
    Object.defineProperty(document, 'readyState', {
      configurable: true,
      get: () => readyStateValue,
    });

    document.body.innerHTML = `
      <form id="jlg-onboarding-form">
        <div class="jlg-onboarding-feedback"></div>
        <div class="jlg-onboarding-step is-active">
          <label><input type="checkbox" name="allowed_post_types[]" value="post" checked>Post</label>
        </div>
        <div class="jlg-onboarding-step">
          <label><input type="checkbox" name="modules[]" value="module" checked>Module</label>
        </div>
        <div class="jlg-onboarding-step">
          <label><input type="radio" name="visual_preset" value="minimal" checked>Minimal</label>
          <label><input type="radio" name="visual_theme" value="light" checked>Light</label>
        </div>
        <div class="jlg-onboarding-step">
          <input type="text" name="rawg_api_key" value="1234567890" />
          <input type="checkbox" name="rawg_skip" />
        </div>
        <button type="button" class="jlg-onboarding-prev">Prev</button>
        <button type="button" class="jlg-onboarding-next">Next</button>
        <button type="submit" class="jlg-onboarding-submit">Submit</button>
        <input type="hidden" id="jlg-onboarding-current-step" value="1" />
      </form>
      <ol class="jlg-onboarding-progress">
        <li class="jlg-onboarding-progress__item"></li>
        <li class="jlg-onboarding-progress__item"></li>
        <li class="jlg-onboarding-progress__item"></li>
        <li class="jlg-onboarding-progress__item"></li>
      </ol>
    `;

    window.fetch = jest.fn(() => Promise.resolve({ ok: true }));
    window.jlgOnboarding = {
      stepCount: 4,
      telemetry: {
        endpoint: '/track',
        action: 'jlg_onboarding_track',
        nonce: 'nonce',
        debug: true,
      },
    };

    require('../onboarding.js');
    document.dispatchEvent(new Event('DOMContentLoaded'));
    readyStateValue = 'complete';

    if (window.jlgOnboardingTracker && typeof window.jlgOnboardingTracker.resetDebugEvents === 'function') {
      window.jlgOnboardingTracker.resetDebugEvents();
    }

    if (typeof window.fetch.mockClear === 'function') {
      window.fetch.mockClear();
    }
  });

  afterEach(() => {
    jest.resetModules();
    if (ORIGINAL_READY_STATE) {
      Object.defineProperty(document, 'readyState', ORIGINAL_READY_STATE);
    } else {
      delete document.readyState;
    }
    delete window.jlgOnboarding;
    delete window.jlgOnboardingTracker;
    window.fetch = undefined;
    document.body.innerHTML = '';
  });

  test('records validation error when required fields are missing', () => {
    const postCheckbox = document.querySelector('input[name="allowed_post_types[]"]');
    postCheckbox.checked = false;

    const nextButton = document.querySelector('.jlg-onboarding-next');
    nextButton.click();

    const events = window.jlgOnboardingTracker.getDebugEvents();
    const validationError = events.find((entry) => entry.event === 'validation' && entry.payload.status === 'error');
    expect(validationError).toBeDefined();
    expect(validationError.payload.feedback_code).toBe('missing_post_type');

    const validationFetch = window.fetch.mock.calls.filter(([, options]) => {
      const entries = Array.from(options.body.entries());
      return entries.some(([key, value]) => key === 'event' && value === 'validation');
    });
    expect(validationFetch.length).toBeGreaterThan(0);

    const payloadEntry = Array.from(validationFetch[0][1].body.entries()).find(([key]) => key === 'payload');
    expect(payloadEntry).toBeDefined();
    const payload = JSON.parse(payloadEntry[1]);
    expect(payload.feedback_code).toBe('missing_post_type');
    expect(payload.status).toBe('error');
  });

  test('tracks successful navigation and submission', () => {
    const nextButton = document.querySelector('.jlg-onboarding-next');
    nextButton.click();
    nextButton.click();
    nextButton.click();

    const form = document.getElementById('jlg-onboarding-form');
    form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));

    const events = window.jlgOnboardingTracker.getDebugEvents();
    const submissionEvent = events.find((entry) => entry.event === 'submission' && entry.payload.status === 'success');
    expect(submissionEvent).toBeDefined();
    expect(submissionEvent.payload.feedback_code).toBe('submitted');

    const completionEvent = events.find((entry) => entry.event === 'step_leave' && entry.payload.direction === 'complete');
    expect(completionEvent).toBeDefined();
    expect(completionEvent.payload.duration).toBeGreaterThanOrEqual(0);

    const submissionFetch = window.fetch.mock.calls.filter(([, options]) => {
      const entries = Array.from(options.body.entries());
      return entries.some(([key, value]) => key === 'event' && value === 'submission');
    });
    expect(submissionFetch.length).toBeGreaterThan(0);

    const payloadEntry = Array.from(submissionFetch.pop()[1].body.entries()).find(([key]) => key === 'payload');
    expect(payloadEntry).toBeDefined();
    const payload = JSON.parse(payloadEntry[1]);
    expect(payload.status).toBe('success');
    expect(payload.feedback_code).toBe('submitted');
  });
});
