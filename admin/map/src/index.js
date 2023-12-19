import ReactDOM from 'react-dom/client'
import App from './app'

function getRootElements() {
  return document.querySelectorAll('#wp-map-plugin-page-root')
}

const rootElements = getRootElements()

rootElements.forEach(rootElement => {
  const tagSlug = getAttribute(rootElement, 'data-tag-slug', '')
  const view = getAttribute(rootElement, 'data-view', 'map')
  const height = getAttribute(rootElement, 'data-height', 50, 'number')
  const width = getAttribute(rootElement, 'data-width', 75, 'number')
  const linkType = getAttribute(rootElement, 'data-link-type', 'primary')

  ReactDOM.createRoot(rootElement).render(
    <App
      tagSlug={tagSlug}
      view={view}
      height={height}
      width={width}
      linkType={linkType}
    />
  )
})

function getAttribute(element, name, defaultValue, type = 'string') {
  const value = element.getAttribute(name)

  if (type === 'number') {
    const number = parseInt(value)
    if (isNaN(number)) {
      return defaultValue
    }
    return number
  }

  if (value) {
    return value
  }
  return defaultValue
}
