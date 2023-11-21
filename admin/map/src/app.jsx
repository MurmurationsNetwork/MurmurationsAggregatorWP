import PropTypes from 'prop-types'
import { useEffect, useState } from 'react'
import MapClient from './components/mapClient'
import Directory from './components/directory'

export default function App(props) {
  // eslint-disable-next-line no-undef
  const wordpressUrl = murmurations_aggregator.wordpress_url
  const apiUrl = `${wordpressUrl}/wp-json/murmurations-aggregator/v1`

  const { tagSlug, view, height } = props

  const [profiles, setProfiles] = useState([])
  const [map, setMap] = useState({})
  const [isMapLoaded, setIsMapLoaded] = useState(false)

  useEffect(() => {
    async function fetchData() {
      try {
        await Promise.all([getProfiles(), getMap()])
        setIsMapLoaded(true)
      } catch (error) {
        alert(
          `Error getting profiles, please contact the administrator, error: ${error}`
        )
      }
    }

    fetchData().then(() => console.log('fetched data'))
  }, [])

  const getProfiles = async () => {
    try {
      let response
      if (view === 'dir') {
        response = await fetch(`${apiUrl}/maps/${tagSlug}?view=dir`)
      } else {
        response = await fetch(`${apiUrl}/maps/${tagSlug}`)
      }
      const data = await response.json()
      setProfiles(data)
    } catch (error) {
      alert(
        `Error getting profiles, please contact the administrator, error: ${error}`
      )
    }
  }

  const getMap = async () => {
    try {
      const response = await fetch(`${apiUrl}/api/maps`)
      const data = await response.json()
      const map = data.find(map => map.tag_slug === tagSlug)
      setMap(map)
    } catch (error) {
      alert(
        `Error getting maps, please contact the administrator, error: ${error}`
      )
    }
  }

  return (
    <div>
      {view === 'dir' ? (
        <Directory profiles={profiles} />
      ) : (
        <MapClient
          profiles={profiles}
          apiUrl={apiUrl}
          map={map}
          isMapLoaded={isMapLoaded}
          height={height}
        />
      )}
    </div>
  )
}

App.propTypes = {
  tagSlug: PropTypes.string.isRequired,
  view: PropTypes.string.isRequired,
  height: PropTypes.number.isRequired
}
