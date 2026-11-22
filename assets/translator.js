import { localeFallbacks, messages } from '../var/translations';
import { createTranslator, getDefaultLocale } from '@symfony/ux-translator';

const translator = createTranslator({ messages, localeFallbacks });

const trans = translator.trans;
const setLocale = translator.setLocale;
const getLocale = translator.getLocale;

export { trans, setLocale, getLocale };