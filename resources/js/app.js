import { initCookieConsent } from './cookie-consent';
import { initAcquisition } from './acquisition';
import { initMetrikaGoal } from './metrika-goal';
import { initGuideView } from './guide-view';

initGuideView();
initCookieConsent(initAcquisition());
initMetrikaGoal();
