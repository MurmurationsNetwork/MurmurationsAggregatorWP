import ReactDOM from 'react-dom/client'
import App from './app'

const tagSlug = document
  .querySelector('#wp-map-plugin-page-root')
  .getAttribute('data-tag-slug')

let view = document.querySelector('#wp-map-plugin-page-root').getAttribute('data-view')

if (view !== 'map' && view !== 'dict') {
  view = 'map'
}

ReactDOM.createRoot(document.querySelector('#wp-map-plugin-page-root')).render(
  <App tagSlug={tagSlug} view={view} />
)
