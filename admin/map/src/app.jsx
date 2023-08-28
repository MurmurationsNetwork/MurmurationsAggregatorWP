import PropTypes from "prop-types";

export default function App(props) {
  return (
    <div>
      <h1 className="text-3xl">Murmurations Map</h1>
      <h2 className="text-xl">{props.tagSlug}</h2>
    </div>
  )
}

App.propTypes = {
  tagSlug: PropTypes.string.isRequired,
};