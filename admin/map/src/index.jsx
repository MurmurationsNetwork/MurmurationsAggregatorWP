import ReactDOM from 'react-dom/client'
import App from './app'

const tagSlug = document.querySelector('#wp-map-plugin-page-root').getAttribute('data-tag-slug');

ReactDOM.createRoot(
  document.querySelector('#wp-map-plugin-page-root')
).render(<App tagSlug={tagSlug}/>)
