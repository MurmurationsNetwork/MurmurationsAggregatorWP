import PropTypes from 'prop-types'
import { useEffect, useState } from 'react'
import MapClient from './components/mapClient'

export default function App(props) {
  // eslint-disable-next-line no-undef
  const wordpressUrl = murmurations_aggregator.wordpress_url
  const apiUrl = `${wordpressUrl}/wp-json/murmurations-aggregator/v1`

  const { tagSlug } = props

  const [profiles, setProfiles] = useState([])

  useEffect(() => {
    getProfiles().then(() => {
      console.log('profiles are loaded')
    })
  }, [])

  const getProfiles = async () => {
    try {
      console.log(`${apiUrl}/maps/${tagSlug}`)
      const response = await fetch(`${apiUrl}/maps/${tagSlug}`)
      const data = await response.json()
      setProfiles(data)
    } catch (error) {
      alert(
        `Error getting profiles, please contact the administrator, error: ${error}`
      )
    }
  }

  return (
    <div>
      <h1 className="text-3xl">Murmurations Map - {tagSlug}</h1>
      <MapClient
        profiles={profiles}
        lat={46.603354}
        lon={1.888334}
        zoom={5}
        apiUrl={apiUrl}
      />
    </div>
  )
}

App.propTypes = {
  tagSlug: PropTypes.string.isRequired
}
